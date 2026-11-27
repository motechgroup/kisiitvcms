<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use App\Services\TicketFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $timeline = (string) $request->query('timeline', 'all');
        $status = (string) $request->query('status', 'all');

        $events = Event::withCount([
            'tickets',
            'tickets as checked_in_tickets_count' => fn ($q) => $q->where('status', 'checked_in'),
            'orders as paid_orders_count' => fn ($q) => $q->where('status', 'paid'),
        ])
            ->withSum(['orders as paid_revenue' => fn ($q) => $q->where('status', 'paid')], 'total_amount')
            ->with('ticketTypes')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('venue', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($timeline === 'upcoming', fn ($q) => $q->upcoming())
            ->when($timeline === 'past', fn ($q) => $q->past())
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->latest('starts_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_events' => Event::count(),
            'upcoming_events' => Event::upcoming()->count(),
            'past_events' => Event::past()->count(),
            'published_events' => Event::where('is_published', true)->count(),
            'tickets_issued' => EventTicket::count(),
            'checked_in_tickets' => EventTicket::where('status', 'checked_in')->count(),
            'total_revenue' => (float) EventTicketOrder::where('status', 'paid')->sum('total_amount'),
        ];

        return view('admin.events.index', compact('events', 'stats', 'search', 'timeline', 'status'));
    }

    /**
     * Detailed single-event analytics view: revenue, tickets sold, tier breakdown, and attendees/users.
     */
    public function show(Event $event): View
    {
        $event->load(['ticketTypes' => fn ($q) => $q->withCount('tickets')]);

        $paidOrders = $event->orders()->where('status', 'paid')->with(['user', 'ticketType'])->latest('paid_at')->get();
        $pendingOrdersCount = $event->orders()->where('status', 'pending')->count();
        $ticketsIssued = $event->tickets()->count();
        $checkedInCount = $event->tickets()->where('status', 'checked_in')->count();
        $totalRevenue = (float) $paidOrders->sum('total_amount');

        $totalCapacity = $event->ticketTypes->every(fn ($t) => $t->quantity_available !== null)
            ? (int) $event->ticketTypes->sum('quantity_available')
            : null;

        $tierBreakdown = $event->ticketTypes->map(function ($tier) use ($paidOrders): array {
            $tierRevenue = (float) $paidOrders->where('event_ticket_type_id', $tier->id)->sum('total_amount');

            return [
                'id' => $tier->id,
                'name' => $tier->name,
                'price' => (float) $tier->price,
                'sold' => (int) $tier->quantity_sold,
                'capacity' => $tier->quantity_available,
                'remaining' => $tier->remaining(),
                'revenue' => $tierRevenue,
                'percent' => $tier->quantity_available ? min(100, (int) round(($tier->quantity_sold / max(1, $tier->quantity_available)) * 100)) : null,
            ];
        });

        // Group attendees/buyers cleanly by user_id or phone/email
        $attendees = $paidOrders
            ->groupBy(fn (EventTicketOrder $o) => $o->user_id ? 'u:'.$o->user_id : 'c:'.($o->buyer_phone ?: $o->buyer_email ?: $o->id))
            ->map(function ($group): array {
                /** @var EventTicketOrder $first */
                $first = $group->first();

                return [
                    'user' => $first->user,
                    'name' => $first->user?->name ?: $first->buyer_name,
                    'phone' => $first->buyer_phone ?: $first->user?->phone,
                    'email' => $first->buyer_email,
                    'orders_count' => $group->count(),
                    'tickets_count' => (int) $group->sum('quantity'),
                    'total_spent' => (float) $group->sum('total_amount'),
                    'last_purchased_at' => $first->paid_at ?? $first->created_at,
                    'sms_sent' => $group->contains(fn ($o) => $o->sms_sent_at !== null),
                    'email_sent' => $group->contains(fn ($o) => $o->email_sent_at !== null),
                ];
            })
            ->values();

        $metrics = [
            'revenue' => $totalRevenue,
            'paid_orders' => $paidOrders->count(),
            'pending_orders' => $pendingOrdersCount,
            'tickets_issued' => $ticketsIssued,
            'checked_in' => $checkedInCount,
            'check_in_rate' => $ticketsIssued > 0 ? (int) round(($checkedInCount / $ticketsIssued) * 100) : 0,
            'capacity' => $totalCapacity,
            'unique_attendees' => $attendees->count(),
            'sms_delivered' => $paidOrders->whereNotNull('sms_sent_at')->count(),
            'email_delivered' => $paidOrders->whereNotNull('email_sent_at')->count(),
        ];

        return view('admin.events.show', compact('event', 'metrics', 'tierBreakdown', 'attendees', 'paidOrders'));
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event([
                'currency' => 'KES',
                'is_published' => true,
                'starts_at' => now()->addDays(7)->setTime(18, 0),
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $coverImage = $this->uploadEventImage($request, 'cover_image_file', 'cover');
        $flyerImage = $this->uploadEventImage($request, 'flyer_image_file', 'flyer');

        $event = Event::create([
            'title' => $validated['title'],
            'slug' => Event::generateUniqueSlug($validated['title']),
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'],
            'city' => $validated['city'] ?? null,
            'cover_image' => $coverImage,
            'flyer_image' => $flyerImage,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'KES'),
            'is_published' => $request->boolean('is_published', true),
            'is_featured' => $request->boolean('is_featured', false),
            'created_by' => auth()->id(),
        ]);

        $this->syncTicketTypes($event, $validated['ticket_types']);

        return redirect()->route('admin.events.index')
            ->with('success', "Event '{$event->title}' created and ready for ticket sales.");
    }

    public function edit(Event $event): View
    {
        $event->load('ticketTypes');

        return view('admin.events.form', [
            'event' => $event,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $coverImage = $this->uploadEventImage($request, 'cover_image_file', 'cover', $event->cover_image);
        $flyerImage = $this->uploadEventImage($request, 'flyer_image_file', 'flyer', $event->flyer_image);

        $event->update([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'],
            'city' => $validated['city'] ?? null,
            'cover_image' => $coverImage,
            'flyer_image' => $flyerImage,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'KES'),
            'is_published' => $request->boolean('is_published', false),
            'is_featured' => $request->boolean('is_featured', false),
        ]);

        $this->syncTicketTypes($event, $validated['ticket_types']);

        return redirect()->route('admin.events.index')
            ->with('success', "Event '{$event->title}' updated.");
    }

    public function togglePublished(Event $event): RedirectResponse
    {
        $event->update(['is_published' => ! $event->is_published]);

        $state = $event->is_published ? 'published' : 'hidden';

        return back()->with('success', "Event '{$event->title}' is now {$state}.");
    }

    public function toggleFeatured(Event $event): RedirectResponse
    {
        $event->update(['is_featured' => ! $event->is_featured]);

        $state = $event->is_featured ? 'featured in hero & header banner' : 'removed from featured spotlight';

        return back()->with('success', "Event '{$event->title}' {$state}.");
    }

    public function duplicate(Event $event): RedirectResponse
    {
        $event->load('ticketTypes');

        $copy = Event::create([
            'title' => $event->title.' (Copy)',
            'slug' => Event::generateUniqueSlug($event->title.' Copy'),
            'summary' => $event->summary,
            'description' => $event->description,
            'venue' => $event->venue,
            'city' => $event->city,
            'cover_image' => $event->cover_image,
            'flyer_image' => $event->flyer_image,
            'starts_at' => now()->addDays(14)->setTime(18, 0),
            'ends_at' => $event->ends_at ? now()->addDays(14)->setTime(22, 0) : null,
            'currency' => $event->currency,
            'is_published' => false,
            'is_featured' => false,
            'created_by' => auth()->id(),
        ]);

        foreach ($event->ticketTypes as $tier) {
            $copy->ticketTypes()->create([
                'name' => $tier->name,
                'description' => $tier->description,
                'price' => $tier->price,
                'quantity_available' => $tier->quantity_available,
                'quantity_sold' => 0,
                'is_active' => $tier->is_active,
                'display_order' => $tier->display_order,
            ]);
        }

        return redirect()->route('admin.events.edit', $copy)
            ->with('success', "Event duplicated as draft '{$copy->title}'. Update its dates and publish when ready.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $title = $event->title;
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', "Event '{$title}' deleted.");
    }

    /**
     * View orders & issued tickets for an event, with search and gate check-in.
     */
    public function tickets(Request $request, Event $event): View
    {
        $search = trim((string) $request->query('q', ''));

        $orders = $event->orders()
            ->with(['ticketType', 'tickets'])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('order_reference', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_email', 'like', "%{$search}%")
                        ->orWhere('buyer_phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20, ['*'], 'orders_page');

        $tickets = $event->tickets()
            ->with(['ticketType', 'order', 'checkedInBy'])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('ticket_code', 'like', "%{$search}%")
                        ->orWhere('attendee_name', 'like', "%{$search}%")
                        ->orWhere('attendee_email', 'like', "%{$search}%")
                        ->orWhere('attendee_phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25, ['*'], 'tickets_page');

        return view('admin.events.tickets', compact('event', 'orders', 'tickets', 'search'));
    }

    /**
     * Check in an attendee ticket at the gate.
     */
    public function checkInTicket(EventTicket $ticket): RedirectResponse
    {
        if ($ticket->status === 'checked_in') {
            return back()->with('error', "Ticket {$ticket->ticket_code} was already checked in at {$ticket->checked_in_at?->timezone('Africa/Nairobi')->format('M j, g:i A')}.");
        }

        $ticket->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
            'checked_in_by' => auth()->id(),
        ]);

        return back()->with('success', "Ticket {$ticket->ticket_code} ({$ticket->attendee_name}) checked in successfully.");
    }

    /**
     * Manually mark a pending order as paid, issue tickets, and send SMS + Email.
     */
    public function markOrderPaid(EventTicketOrder $order, TicketFulfillmentService $fulfillment): RedirectResponse
    {
        $fulfillment->fulfillOrder($order, 'ADMIN-'.strtoupper(substr(md5((string) now()), 0, 8)));

        return back()->with('success', "Order {$order->order_reference} marked paid. Digital ticket links sent via SMS & Email.");
    }

    /**
     * Resend the SMS and Email ticket links for a paid order.
     */
    public function resendOrderNotifications(EventTicketOrder $order, TicketFulfillmentService $fulfillment): RedirectResponse
    {
        if ($order->status !== 'paid' || $order->tickets()->doesntExist()) {
            return back()->with('error', 'Only paid orders with issued tickets can be resent.');
        }

        $result = $fulfillment->deliverNotifications($order);
        $channels = [];
        if ($result['email']) {
            $channels[] = 'Email';
        }
        if ($result['sms']) {
            $channels[] = 'SMS';
        }

        $summary = $channels !== [] ? implode(' & ', $channels) : 'logs';

        return back()->with('success', "Ticket link resent for {$order->order_reference} via {$summary}.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'venue' => ['required', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'cover_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'flyer_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'ticket_types' => ['required', 'array', 'min:1'],
            'ticket_types.*.id' => ['nullable', 'integer'],
            'ticket_types.*.name' => ['required', 'string', 'max:120'],
            'ticket_types.*.description' => ['nullable', 'string', 'max:400'],
            'ticket_types.*.price' => ['required', 'numeric', 'min:0'],
            'ticket_types.*.quantity_available' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    protected function uploadEventImage(Request $request, string $field, string $prefix, ?string $existing = null): ?string
    {
        if (! $request->hasFile($field)) {
            return $existing;
        }

        $destinationPath = public_path('uploads/events');
        if (! File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file($field);
        $fileName = $prefix.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$file->getClientOriginalExtension();
        $file->move($destinationPath, $fileName);

        return '/uploads/events/'.$fileName;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     */
    protected function syncTicketTypes(Event $event, array $tiers): void
    {
        $keptIds = [];

        foreach (array_values($tiers) as $index => $tier) {
            $data = [
                'name' => trim((string) $tier['name']),
                'description' => isset($tier['description']) && trim((string) $tier['description']) !== '' ? trim((string) $tier['description']) : null,
                'price' => (float) $tier['price'],
                'quantity_available' => isset($tier['quantity_available']) && $tier['quantity_available'] !== '' ? (int) $tier['quantity_available'] : null,
                'is_active' => true,
                'display_order' => $index,
            ];

            if (! empty($tier['id'])) {
                $existing = $event->ticketTypes()->whereKey($tier['id'])->first();
                if ($existing) {
                    $existing->update($data);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $event->ticketTypes()->create($data);
            $keptIds[] = $created->id;
        }

        $event->ticketTypes()
            ->whereNotIn('id', $keptIds)
            ->where('quantity_sold', 0)
            ->delete();
    }
}
