<?php

namespace App\Services;

use App\Mail\EventTicketMail;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketFulfillmentService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Mark an order as paid, issue its digital tickets (idempotently), and send SMS + Email.
     */
    public function fulfillOrder(EventTicketOrder $order, ?string $externalReference = null): EventTicketOrder
    {
        $wasAlreadyFulfilled = false;

        DB::transaction(function () use ($order, $externalReference, &$wasAlreadyFulfilled): void {
            /** @var EventTicketOrder $locked */
            $locked = EventTicketOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'paid' && $locked->tickets()->exists()) {
                $wasAlreadyFulfilled = true;

                return;
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => $locked->paid_at ?? now(),
                'external_reference' => $externalReference ?: $locked->external_reference,
            ]);

            $locked->ticketType()->increment('quantity_sold', $locked->quantity);

            for ($i = 0; $i < $locked->quantity; $i++) {
                EventTicket::create([
                    'event_ticket_order_id' => $locked->id,
                    'event_id' => $locked->event_id,
                    'event_ticket_type_id' => $locked->event_ticket_type_id,
                    'user_id' => $locked->user_id,
                    'ticket_code' => EventTicket::generateTicketCode(),
                    'access_token' => EventTicket::generateAccessToken(),
                    'attendee_name' => $locked->buyer_name,
                    'attendee_email' => $locked->buyer_email,
                    'attendee_phone' => $locked->buyer_phone,
                    'status' => 'valid',
                ]);
            }
        });

        $order->refresh()->load(['event', 'ticketType', 'tickets']);

        if (! $wasAlreadyFulfilled) {
            $this->deliverNotifications($order);
        }

        return $order;
    }

    /**
     * Send (or resend) the ticket link(s) to the buyer via Email and SMS.
     *
     * @return array{email: bool, sms: bool}
     */
    public function deliverNotifications(EventTicketOrder $order): array
    {
        $order->loadMissing(['event', 'ticketType', 'tickets']);

        $emailSent = false;
        $smsSent = false;

        // 1. Send Email with ticket link(s) when a real email address was provided
        try {
            if (filled($order->buyer_email) && ! str_ends_with(strtolower($order->buyer_email), '@guests.kisiitv.co.ke')) {
                Mail::to($order->buyer_email)->send(new EventTicketMail($order));
                $emailSent = true;
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending event ticket email', [
                'order' => $order->order_reference,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Send SMS with primary ticket link
        try {
            $firstTicket = $order->tickets->first();
            if ($firstTicket && filled($order->buyer_phone)) {
                $dateText = $order->event->starts_at->timezone('Africa/Nairobi')->format('M j, Y g:iA');
                $extraNote = $order->tickets->count() > 1
                    ? ' (+'.($order->tickets->count() - 1).' more on your pass)'
                    : '';

                $smsText = "Kisii TV Ticket Confirmed! {$order->event->title} ({$dateText}). "
                    ."Code: {$firstTicket->ticket_code}{$extraNote}. "
                    ."Open your ticket: {$firstTicket->passUrl()}";

                $smsSent = $this->smsService->send($order->buyer_phone, $smsText);
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending event ticket SMS', [
                'order' => $order->order_reference,
                'error' => $e->getMessage(),
            ]);
        }

        $updates = [];
        if ($emailSent) {
            $updates['email_sent_at'] = now();
        }
        if ($smsSent) {
            $updates['sms_sent_at'] = now();
        }
        if ($updates !== []) {
            $order->update($updates);
        }

        return ['email' => $emailSent, 'sms' => $smsSent];
    }
}
