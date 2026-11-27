<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use App\Models\EventTicketType;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use App\Services\TicketFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $gatewayService,
        protected TicketFulfillmentService $fulfillmentService,
        protected SmsService $smsService
    ) {}

    /**
     * Modern public events listing with Upcoming / Past tabs, search, and pagination.
     */
    public function index(Request $request): View
    {
        $navCategories = Category::cachedNavCategories();
        $tab = in_array($request->query('tab'), ['upcoming', 'past'], true)
            ? (string) $request->query('tab')
            : 'upcoming';
        $search = trim((string) $request->query('q', ''));

        $upcomingCount = Event::published()->upcoming()->count();
        $pastCount = Event::published()->past()->count();

        $query = Event::published()
            ->with('activeTicketTypes')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('venue', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            });

        if ($tab === 'past') {
            $events = $query->past()->orderByDesc('starts_at')->paginate(9)->withQueryString();
        } else {
            $events = $query->upcoming()->orderByDesc('is_featured')->orderBy('starts_at')->paginate(9)->withQueryString();
        }

        // Also show a compact preview of recent past events when on the default upcoming tab (page 1, no search)
        $recentPastEvents = ($tab === 'upcoming' && $search === '' && (int) $request->query('page', 1) === 1)
            ? Event::published()->past()->with('activeTicketTypes')->orderByDesc('starts_at')->take(3)->get()
            : collect();

        return view('events.index', compact(
            'events',
            'recentPastEvents',
            'tab',
            'search',
            'upcomingCount',
            'pastCount',
            'navCategories'
        ));
    }

    /**
     * Public event details & frictionless ticket checkout page.
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
     * Lightweight lookup so returning buyers are automatically remembered by phone number.
     */
    public function lookupAttendee(Request $request): JsonResponse
    {
        $rawPhone = trim((string) $request->query('phone', ''));
        if (strlen(preg_replace('/\D/', '', $rawPhone) ?? '') < 9) {
            return response()->json(['found' => false]);
        }

        $normalized = $this->smsService->normalizePhone($rawPhone);

        $user = User::where('phone', $normalized)
            ->orWhere('phone', ltrim($normalized, '+'))
            ->orWhere('phone', $rawPhone)
            ->first();

        if (! $user) {
            return response()->json(['found' => false]);
        }

        $realEmail = str_ends_with(strtolower((string) $user->email), '@guests.kisiitv.co.ke')
            ? ''
            : (string) $user->email;

        return response()->json([
            'found' => true,
            'name' => $user->name,
            'email' => $realEmail,
        ]);
    }

    /**
     * Create a ticket order (phone OR email OR both; auto-creates/remembers user by phone).
     */
    public function purchase(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published, 404);

        if ($event->isPast()) {
            return back()->with('error', 'Ticket sales have closed because this event has already ended.');
        }

        $validated = $request->validate([
            'event_ticket_type_id' => ['required', 'integer', 'exists:event_ticket_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'buyer_email' => ['nullable', 'required_without:buyer_phone', 'email', 'max:150'],
            'buyer_phone' => ['nullable', 'required_without:buyer_email', 'string', 'max:40'],
            'gateway_code' => ['nullable', 'string', 'max:50'],
        ], [
            'buyer_email.required_without' => 'Please enter either your phone number or email address so we can send your ticket link.',
            'buyer_phone.required_without' => 'Please enter either your phone number or email address so we can send your ticket link.',
        ]);

        /** @var EventTicketType $ticketType */
        $ticketType = $event->activeTicketTypes()
            ->whereKey($validated['event_ticket_type_id'])
            ->firstOrFail();

        $quantity = (int) $validated['quantity'];

        if (! $ticketType->hasCapacityFor($quantity)) {
            return back()->withInput()->with('error', "Only {$ticketType->remaining()} tickets remain for {$ticketType->name}.");
        }

        $normalizedPhone = filled($validated['buyer_phone'] ?? null)
            ? $this->smsService->normalizePhone((string) $validated['buyer_phone'])
            : null;
        $buyerEmail = filled($validated['buyer_email'] ?? null)
            ? trim((string) $validated['buyer_email'])
            : null;
        $buyerName = filled($validated['buyer_name'] ?? null)
            ? trim((string) $validated['buyer_name'])
            : null;

        // Automatically find or create a User account when a phone number (or email) is provided
        $user = $this->resolveOrCreateBuyerAccount($normalizedPhone, $buyerEmail, $buyerName);
        $resolvedName = $buyerName ?: ($user?->name ?: 'Event Guest');

        $unitPrice = (float) $ticketType->price;
        $totalAmount = round($unitPrice * $quantity, 2);

        // Free ticket: fulfill immediately & send SMS and/or Email
        if ($totalAmount <= 0) {
            $order = EventTicketOrder::create([
                'event_id' => $event->id,
                'event_ticket_type_id' => $ticketType->id,
                'user_id' => $user?->id,
                'gateway_code' => 'free',
                'order_reference' => EventTicketOrder::generateReference(),
                'buyer_name' => $resolvedName,
                'buyer_email' => $buyerEmail,
                'buyer_phone' => $normalizedPhone,
                'quantity' => $quantity,
                'unit_price' => 0,
                'total_amount' => 0,
                'currency' => $event->currency ?: 'KES',
                'status' => 'pending',
            ]);

            $order = $this->fulfillmentService->fulfillOrder($order, 'FREE-RSVP');
            $firstTicket = $order->tickets->firstOrFail();

            return redirect()->route('tickets.show', $firstTicket->access_token)
                ->with('success', 'Your free ticket is confirmed! Your digital ticket link has been sent.');
        }

        $gatewayCode = $validated['gateway_code'] ?: PaymentGateway::where('is_active', true)->orderBy('display_order')->value('code');
        $gateway = PaymentGateway::where('code', $gatewayCode)->where('is_active', true)->first();

        if (! $gateway) {
            return back()->withInput()->with('error', 'No active payment gateway is currently available. Please try again shortly.');
        }

        $order = EventTicketOrder::create([
            'event_id' => $event->id,
            'event_ticket_type_id' => $ticketType->id,
            'user_id' => $user?->id,
            'payment_gateway_id' => $gateway->id,
            'gateway_code' => $gateway->code,
            'order_reference' => EventTicketOrder::generateReference(),
            'buyer_name' => $resolvedName,
            'buyer_email' => $buyerEmail,
            'buyer_phone' => $normalizedPhone,
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

            $paystackEmail = $buyerEmail ?: ($user?->email ?: 'tickets@kisiitv.co.ke');

            $init = $this->gatewayService->initializePaystackPayment(
                gateway: $gateway,
                amount: $totalAmount,
                email: $paystackEmail,
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
     * Find an existing user by phone (or email), or auto-create a lightweight account
     * when a phone number is provided so the system remembers them on future bookings.
     */
    protected function resolveOrCreateBuyerAccount(?string $normalizedPhone, ?string $email, ?string $name): ?User
    {
        if (auth()->check()) {
            $authUser = auth()->user();
            if ($normalizedPhone && blank($authUser->phone)) {
                $authUser->update(['phone' => $normalizedPhone]);
            }

            return $authUser;
        }

        $user = null;

        if ($normalizedPhone) {
            $user = User::where('phone', $normalizedPhone)
                ->orWhere('phone', ltrim($normalizedPhone, '+'))
                ->first();
        }

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            $updates = [];
            if ($normalizedPhone && blank($user->phone)) {
                $updates['phone'] = $normalizedPhone;
            }
            if ($name && ($user->name === 'Event Guest' || str_starts_with($user->name, 'Guest '))) {
                $updates['name'] = $name;
            }
            if ($email && str_ends_with(strtolower($user->email), '@guests.kisiitv.co.ke') && ! User::where('email', $email)->exists()) {
                $updates['email'] = $email;
            }
            if ($updates !== []) {
                $user->update($updates);
            }

            return $user;
        }

        // Auto-create account when user provides their phone number (no signup friction!)
        if (! $normalizedPhone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $normalizedPhone) ?: Str::random(8);
        $accountEmail = $email ?: "{$digits}@guests.kisiitv.co.ke";
        $baseUsername = 'guest_'.substr($digits, -9);
        $username = $baseUsername;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = "{$baseUsername}_{$suffix}";
            $suffix++;
        }

        return User::create([
            'name' => $name ?: 'Guest '.substr($digits, -4),
            'username' => $username,
            'email' => $accountEmail,
            'phone' => $normalizedPhone,
            'password' => Hash::make(Str::random(24)),
            'is_active' => true,
        ]);
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
            ->with('success', 'Payment confirmed! Your digital ticket link has been sent.');
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
            ->with('success', 'Ticket confirmed! Your digital pass link has been sent.');
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
