@extends('layouts.admin')

@section('title', 'Events & Ticketing Dashboard')
@section('page_title', 'Events & Ticketing Command Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 rounded-xl shadow-2xs">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Events &amp; Digital Tickets Dashboard</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Create events, monitor ticket revenue &amp; check-ins, filter upcoming/past events, and deliver passes via TextSMS &amp; Email.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('admin.settings.index', ['tab' => 'sms']) }}"
                class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold uppercase tracking-wider rounded-lg transition">
                Test SMS Gateway
            </a>
            <a href="{{ route('events.index') }}" target="_blank"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold uppercase tracking-wider rounded-lg transition">
                Public /events &nearr;
            </a>
            <a href="{{ route('admin.events.create') }}"
                class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition shadow-xs">
                + Create Event
            </a>
        </div>
    </div>

    <!-- Rich KPI Stats Strip -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Events</div>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($stats['total_events']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Upcoming</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['upcoming_events']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Past Events</div>
            <div class="text-2xl font-black text-slate-600 mt-1">{{ number_format($stats['past_events']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tickets Issued</div>
            <div class="text-2xl font-black text-[#1b52bc] mt-1">{{ number_format($stats['tickets_issued']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Checked In</div>
            <div class="text-2xl font-black text-indigo-700 mt-1">{{ number_format($stats['checked_in_tickets']) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Revenue</div>
            <div class="text-xl font-black text-emerald-700 mt-1">KES {{ number_format($stats['total_revenue'], 0) }}</div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('admin.events.index') }}"
        class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col md:flex-row items-stretch md:items-center gap-3 text-xs">
        <div class="flex-1">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search by event title, venue, or city..."
                class="w-full border border-slate-300 rounded-lg px-3.5 py-2 focus:outline-none focus:border-[#1b52bc]">
        </div>

        <select name="timeline" class="border border-slate-300 rounded-lg px-3 py-2 bg-white font-semibold">
            <option value="all" {{ $timeline === 'all' ? 'selected' : '' }}>All Dates</option>
            <option value="upcoming" {{ $timeline === 'upcoming' ? 'selected' : '' }}>Upcoming Events</option>
            <option value="past" {{ $timeline === 'past' ? 'selected' : '' }}>Past Events</option>
        </select>

        <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 bg-white font-semibold">
            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published Only</option>
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Drafts Only</option>
        </select>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-[#0a1c3d] hover:bg-slate-800 text-white font-bold uppercase rounded-lg cursor-pointer">
                Filter
            </button>
            @if($search !== '' || $timeline !== 'all' || $status !== 'all')
                <a href="{{ route('admin.events.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Events Management Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        @if($events->isEmpty())
            <div class="p-12 text-center">
                <h3 class="text-base font-bold text-slate-800">No matching events found</h3>
                <p class="text-xs text-slate-500 mt-1">Adjust your filters or create a new Kisii TV event to start selling digital tickets.</p>
                <a href="{{ route('admin.events.create') }}"
                    class="inline-block mt-4 px-5 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                    + Create Event
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Date &amp; Venue</th>
                            <th class="px-4 py-3 text-left">Sales &amp; Check-Ins</th>
                            <th class="px-4 py-3 text-left">Visibility</th>
                            <th class="px-4 py-3 text-right">Management Actions</th>
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
                                            <a href="{{ route('admin.events.show', $event) }}" class="font-bold text-slate-950 hover:text-[#1b52bc] truncate block max-w-xs">
                                                {{ $event->title }}
                                            </a>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] font-mono text-slate-400">/events/{{ $event->slug }}</span>
                                                @if($event->isPast())
                                                    <span class="px-1.5 py-0.2 rounded bg-slate-200 text-slate-600 text-[9px] font-bold uppercase">Past</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold text-slate-900">{{ $event->starts_at->timezone('Africa/Nairobi')->format('M j, Y · g:i A') }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-950">
                                        {{ $event->tickets_count }} sold
                                        <span class="text-[10px] text-indigo-600 font-semibold">({{ $event->checked_in_tickets_count }} in)</span>
                                    </div>
                                    <div class="text-[11px] font-mono font-bold text-emerald-700">{{ $event->currency }} {{ number_format((float) ($event->paid_revenue ?? 0), 0) }}</div>
                                </td>
                                <td class="px-4 py-3 space-y-1">
                                    <form method="POST" action="{{ route('admin.events.toggle-published', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider cursor-pointer {{ $event->is_published ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}">
                                            {{ $event->is_published ? 'Published' : 'Draft' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.events.toggle-featured', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider cursor-pointer {{ $event->is_featured ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200' }}">
                                            {{ $event->is_featured ? '★ Featured' : 'Standard' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('admin.events.show', $event) }}"
                                        class="inline-block px-2.5 py-1.5 rounded bg-[#0a1c3d] hover:bg-slate-800 text-white font-bold text-[11px]">
                                        Details
                                    </a>
                                    <a href="{{ route('admin.events.tickets', $event) }}"
                                        class="inline-block px-2.5 py-1.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]">
                                        Tickets
                                    </a>
                                    <a href="{{ route('admin.events.edit', $event) }}"
                                        class="inline-block px-2.5 py-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px]">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.events.duplicate', $event) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="px-2.5 py-1.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[11px] cursor-pointer">
                                            Clone
                                        </button>
                                    </form>
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
