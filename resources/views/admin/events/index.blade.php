@extends('layouts.admin')

@section('title', 'Events & Ticketing')
@section('page_title', 'Events & Ticketing Manager')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 rounded-xl shadow-2xs">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Events &amp; Digital Tickets</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Create events, manage ticket tiers (Regular, VIP, Early Bird), track sales, check in guests, and deliver ticket links via SMS &amp; Email.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('events.index') }}" target="_blank"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold uppercase tracking-wider rounded-lg transition">
                Public Events Page &nearr;
            </a>
            <a href="{{ route('admin.events.create') }}"
                class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition shadow-xs">
                + Create Event
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Events</div>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($stats['total_events']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Published Live</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['published_events']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tickets Issued</div>
            <div class="text-2xl font-black text-[#1b52bc] mt-1">{{ number_format($stats['tickets_issued']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ticket Revenue</div>
            <div class="text-2xl font-black text-slate-950 mt-1">KES {{ number_format($stats['total_revenue'], 0) }}</div>
        </div>
    </div>

    <!-- Events Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        @if($events->isEmpty())
            <div class="p-12 text-center">
                <h3 class="text-base font-bold text-slate-800">No events created yet</h3>
                <p class="text-xs text-slate-500 mt-1">Create your first Kisii TV live event, concert, town hall, or gala to start selling digital tickets.</p>
                <a href="{{ route('admin.events.create') }}"
                    class="inline-block mt-4 px-5 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                    + Create First Event
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Date &amp; Venue</th>
                            <th class="px-4 py-3 text-left">Ticket Tiers</th>
                            <th class="px-4 py-3 text-left">Sales</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($events as $event)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}"
                                            class="w-14 h-10 rounded-lg object-cover border border-slate-200 shrink-0">
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-950 truncate max-w-xs">{{ $event->title }}</div>
                                            <a href="{{ route('events.show', $event->slug) }}" target="_blank"
                                                class="text-[11px] text-[#1b52bc] hover:underline font-mono">
                                                /events/{{ $event->slug }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold text-slate-900">{{ $event->starts_at->timezone('Africa/Nairobi')->format('M j, Y · g:i A') }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($event->ticketTypes as $tier)
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[10px]">
                                                {{ $tier->name }}: {{ (float) $tier->price > 0 ? $event->currency.' '.number_format((float) $tier->price, 0) : 'Free' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-950">{{ $event->tickets_count }} tickets</div>
                                    <div class="text-[11px] font-semibold text-emerald-700">{{ $event->currency }} {{ number_format((float) ($event->paid_revenue ?? 0), 0) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.events.toggle-published', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider cursor-pointer {{ $event->is_published ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}">
                                            {{ $event->is_published ? 'Published' : 'Draft' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                    <a href="{{ route('admin.events.tickets', $event) }}"
                                        class="inline-block px-2.5 py-1.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]">
                                        Tickets &amp; Check-In
                                    </a>
                                    <a href="{{ route('admin.events.edit', $event) }}"
                                        class="inline-block px-2.5 py-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px]">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="inline"
                                        onsubmit="return confirm('Delete this event and all its ticket records?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-2.5 py-1.5 rounded bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[11px] cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
