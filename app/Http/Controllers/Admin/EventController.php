<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketOrder;
use App\Services\TicketFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::withCount([
            'tickets',
            'orders as paid_orders_count' => fn ($q) => $q->where('status', 'paid'),
        ])
            ->withSum(['orders as paid_revenue' => fn ($q) => $q->where('status', 'paid')], 'total_amount')
            ->with('ticketTypes')
            ->latest('starts_at')
            ->paginate(15);

        $stats = [
            'total_events' => Event::count(),
            'published_events' => Event::where('is_published', true)->count(),
            'tickets_issued' => EventTicket::count(),
            'total_revenue' => (float) EventTicketOrder::where('status', 'paid')->sum('total_amount'),
        ];

        return view('admin.events.index', compact('events', 'stats'));
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

        $coverImage = $this->resolveCoverImage($request);

        $event = Event::create([
            'title' => $validated['title'],
            'slug' => Event::generateUniqueSlug($validated['title']),
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'],
            'city' => $validated['city'] ?? null,
            'cover_image' => $coverImage,
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

        $coverImage = $this->resolveCoverImage($request, $event->cover_image);

        $event->update([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'],
            'city' => $validated['city'] ?? null,
            'cover_image' => $coverImage,
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

        return redirect()->route('admin.events.index')
            ->with('success', "Event '{$event->title}' is now {$state}.");
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
            'cover_image_url' => ['nullable', 'url', 'max:500'],
            'cover_image_file' => ['nullable', 'image', 'max:4096'],
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

    protected function resolveCoverImage(Request $request, ?string $existing = null): ?string
    {
        if ($request->hasFile('cover_image_file')) {
            $path = $request->file('cover_image_file')->store('events', 'public');

            return 'storage/'.$path;
        }

        if ($request->filled('cover_image_url')) {
            return trim((string) $request->input('cover_image_url'));
        }

        return $existing;
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
