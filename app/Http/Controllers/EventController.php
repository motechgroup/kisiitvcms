<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use App\Models\EventTicketType;
use App\Models\PaymentGateway;
use App\Services\PaymentGatewayService;
use App\Services\TicketFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $gatewayService,
        protected TicketFulfillmentService $fulfillmentService
    ) {}

    /**
     * Public listing of published events with a featured hero event and grid.
     */
    public function index(): View
    {
        $navCategories = Category::cachedNavCategories();

        $events = Event::published()
            ->with('activeTicketTypes')
            ->orderByDesc('is_featured')
            ->orderBy('starts_at')
            ->paginate(12);

        return view('events.index', compact('events', 'navCategories'));
    }

    /**
     * Public event details & ticket selection page.
     */
    public function show(Event $event): View
    {
        abort_unless($event->is_published || auth()->check(), 404);

        $event->load('activeTicketTypes');
        $navCategories = Category::cachedNavCategories();
        $activeGateways = PaymentGateway::cachedActivePublic();

        return view('events.show', compact('event', 'navCategories', 'activeGateways'));
    }

    /**
     * Create a ticket order and either issue free tickets immediately or redirect to payment gateway.
     */
    public function purchase(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published, 404);

        $validated = $request->validate([
            'event_ticket_type_id' => ['required', 'integer', 'exists:event_ticket_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'buyer_name' => ['required', 'string', 'max:150'],
            'buyer_email' => ['required', 'email', 'max:150'],
            'buyer_phone' => ['required', 'string', 'max:40'],
            'gateway_code' => ['nullable', 'string', 'max:50'],
        ]);

        /** @var EventTicketType $ticketType */
        $ticketType = $event->activeTicketTypes()
            ->whereKey($validated['event_ticket_type_id'])
            ->firstOrFail();

        $quantity = (int) $validated['quantity'];

        if (! $ticketType->hasCapacityFor($quantity)) {
            return back()->withInput()->with('error', "Only {$ticketType->remaining()} tickets remain for {$ticketType->name}.");
        }

        $unitPrice = (float) $ticketType->price;
        $totalAmount = round($unitPrice * $quantity, 2);

        // Free ticket: fulfill immediately & send SMS + Email
        if ($totalAmount <= 0) {
            $order = EventTicketOrder::create([
                'event_id' => $event->id,
                'event_ticket_type_id' => $ticketType->id,
                'gateway_code' => 'free',
                'order_reference' => EventTicketOrder::generateReference(),
                'buyer_name' => trim($validated['buyer_name']),
                'buyer_email' => trim($validated['buyer_email']),
                'buyer_phone' => trim($validated['buyer_phone']),
                'quantity' => $quantity,
                'unit_price' => 0,
                'total_amount' => 0,
                'currency' => $event->currency ?: 'KES',
                'status' => 'pending',
            ]);

            $order = $this->fulfillmentService->fulfillOrder($order, 'FREE-RSVP');
            $firstTicket = $order->tickets->firstOrFail();

            return redirect()->route('tickets.show', $firstTicket->access_token)
                ->with('success', 'Your free ticket is confirmed! Your ticket link has been sent to your SMS and Email.');
        }

        $gatewayCode = $validated['gateway_code'] ?: PaymentGateway::where('is_active', true)->orderBy('display_order')->value('code');
        $gateway = PaymentGateway::where('code', $gatewayCode)->where('is_active', true)->first();

        if (! $gateway) {
            return back()->withInput()->with('error', 'No active payment gateway is currently available. Please try again shortly.');
        }

        $order = EventTicketOrder::create([
            'event_id' => $event->id,
            'event_ticket_type_id' => $ticketType->id,
            'payment_gateway_id' => $gateway->id,
            'gateway_code' => $gateway->code,
            'order_reference' => EventTicketOrder::generateReference(),
            'buyer_name' => trim($validated['buyer_name']),
            'buyer_email' => trim($validated['buyer_email']),
            'buyer_phone' => trim($validated['buyer_phone']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'currency' => $event->currency ?: ($gateway->currency ?: 'KES'),
            'status' => 'pending',
        ]);

        // Direct hosted Paystack checkout when secret key is configured
        if ($gateway->code === 'paystack') {
            $callbackUrl = route('events.checkout.callback', [
                'gateway' => 'paystack',
                'order' => $order->order_reference,
            ]);

            $init = $this->gatewayService->initializePaystackPayment(
                gateway: $gateway,
                amount: $totalAmount,
                email: $order->buyer_email,
                reference: $order->order_reference,
                callbackUrl: $callbackUrl,
                metadata: [
                    'type' => 'event_ticket',
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'ticket_type' => $ticketType->name,
                    'quantity' => $quantity,
                    'buyer_name' => $order->buyer_name,
                    'buyer_phone' => $order->buyer_phone,
                ]
            );

            if ($init['url']) {
                return redirect()->away($init['url']);
            }

            if ($init['error']) {
                return back()->withInput()->with('error', $init['error']);
            }
        }

        return redirect()->route('events.checkout', $order->order_reference);
    }

    /**
     * Fallback checkout page (for M-Pesa manual / test mode confirmation).
     */
    public function checkout(EventTicketOrder $order): View|RedirectResponse
    {
        $order->load(['event', 'ticketType', 'paymentGateway', 'tickets']);

        if ($order->status === 'paid' && $order->tickets->isNotEmpty()) {
            return redirect()->route('tickets.show', $order->tickets->first()->access_token);
        }

        $navCategories = Category::cachedNavCategories();

        return view('events.checkout', compact('order', 'navCategories'));
    }

    /**
     * Handle hosted gateway return callback (Paystack / Flutterwave).
     */
    public function callback(Request $request, string $gateway): RedirectResponse
    {
        $reference = (string) ($request->query('reference')
            ?? $request->query('trxref')
            ?? $request->query('tx_ref')
            ?? $request->query('order')
            ?? '');

        $order = EventTicketOrder::where('order_reference', $reference)->first();

        if (! $order) {
            return redirect()->route('events.index')
                ->with('error', 'We could not locate that ticket order reference.');
        }

        if ($order->status === 'paid' && $order->tickets()->exists()) {
            return redirect()->route('tickets.show', $order->tickets()->first()->access_token);
        }

        $gw = PaymentGateway::where('code', $gateway)->first();
        $verified = false;

        if ($gw && $gateway === 'paystack') {
            $verified = $this->gatewayService->verifyPaystack($reference, $gw);
        } elseif ($gw && $gateway === 'flutterwave') {
            $txId = (string) ($request->query('transaction_id') ?? '');
            $verified = $txId !== '' && $this->gatewayService->verifyFlutterwave($txId, $gw);
        }

        if (! $verified) {
            return redirect()->route('events.checkout', $order->order_reference)
                ->with('error', 'Payment could not be verified yet. If you were charged, please contact support with reference '.$order->order_reference.'.');
        }

        $order = $this->fulfillmentService->fulfillOrder($order, $reference);
        $firstTicket = $order->tickets->firstOrFail();

        return redirect()->route('tickets.show', $firstTicket->access_token)
            ->with('success', 'Payment confirmed! Your digital ticket link has been sent to your SMS and Email.');
    }

    /**
     * Confirm a test/manual checkout order, issue tickets, and dispatch SMS + Email.
     */
    public function complete(Request $request, EventTicketOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'external_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $order = $this->fulfillmentService->fulfillOrder(
            $order,
            $validated['external_reference'] ?? ('CONFIRMED-'.strtoupper(substr(md5($order->order_reference), 0, 8)))
        );

        $firstTicket = $order->tickets->firstOrFail();

        return redirect()->route('tickets.show', $firstTicket->access_token)
            ->with('success', 'Ticket confirmed! Your digital pass link has been sent to your SMS and Email.');
    }

    /**
     * Public digital ticket pass page (linked from SMS and Email).
     */
    public function showTicket(EventTicket $ticket): View
    {
        $ticket->load(['event', 'ticketType', 'order.tickets']);
        $navCategories = Category::cachedNavCategories();
        $siblingTickets = $ticket->order?->tickets ?? collect([$ticket]);

        return view('tickets.show', compact('ticket', 'siblingTickets', 'navCategories'));
    }
}
