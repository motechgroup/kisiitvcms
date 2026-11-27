<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use App\Models\EventTicketType;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $templateHeading;

    public string $templateIntro;

    public string $templateInstructions;

    public string $templateFooter;

    public function __construct(
        public EventTicketOrder $order,
        public bool $isTestPreview = false
    ) {
        $this->templateHeading = (string) SiteSetting::get(
            'ticket_email_heading',
            'Official Digital Ticket Confirmation'
        );
        $this->templateIntro = (string) SiteSetting::get(
            'ticket_email_intro',
            'Your ticket order is confirmed! Present your digital ticket pass below at the venue entrance for quick check-in.'
        );
        $this->templateInstructions = (string) SiteSetting::get(
            'ticket_email_instructions',
            'Arrive at least 30 minutes before doors open. Keep your ticket link private — each ticket code is valid for a single check-in at the gate.'
        );
        $this->templateFooter = (string) SiteSetting::get(
            'ticket_email_footer',
            'Need help with your ticket? Reply directly to ticket@kisiitv.co.ke for instant support.'
        );
    }

    /**
     * Build a realistic sample order for admin SMTP + template testing.
     */
    public static function makeTestSample(string $recipientEmail): self
    {
        $event = Event::published()->latest('starts_at')->first();

        if (! $event) {
            $event = new Event([
                'title' => 'Kisii TV Cultural & Music Festival 2026',
                'slug' => 'kisii-tv-cultural-music-festival-2026',
                'venue' => 'Gusii Stadium',
                'city' => 'Kisii Town',
                'starts_at' => now()->addDays(14)->setTime(14, 0),
                'currency' => 'KES',
            ]);
        }

        $tier = new EventTicketType([
            'name' => 'VIP Lounge Experience',
            'price' => 3000,
        ]);

        $order = new EventTicketOrder([
            'order_reference' => 'KTV-TKT-TEST0001',
            'buyer_name' => 'Kisii TV Admin (Test)',
            'buyer_email' => $recipientEmail,
            'buyer_phone' => '+254712345678',
            'quantity' => 1,
            'unit_price' => 3000,
            'total_amount' => 3000,
            'currency' => $event->currency ?: 'KES',
            'status' => 'paid',
        ]);

        $sampleTicket = new EventTicket([
            'ticket_code' => 'KTV-TEST-2026',
            'access_token' => 'sample-preview-token',
            'attendee_name' => 'Kisii TV Admin (Test)',
            'attendee_email' => $recipientEmail,
            'attendee_phone' => '+254712345678',
            'status' => 'valid',
        ]);

        $order->setRelation('event', $event);
        $order->setRelation('ticketType', $tier);
        $order->setRelation('tickets', collect([$sampleTicket]));

        return new self($order, true);
    }

    public function envelope(): Envelope
    {
        $templateSubject = (string) SiteSetting::get(
            'ticket_email_subject',
            'Your Ticket for {event_title} — Kisii TV Events'
        );

        $subject = str_replace(
            ['{event_title}', '{order_reference}', '{buyer_name}'],
            [$this->order->event->title, $this->order->order_reference, $this->order->buyer_name],
            $templateSubject
        );

        if ($this->isTestPreview) {
            $subject = '[TEST] '.$subject;
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-ticket',
        );
    }
}
