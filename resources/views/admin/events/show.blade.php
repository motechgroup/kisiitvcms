@extends('layouts.admin')

@section('title', 'Event Analytics — ' . $event->title)
@section('page_title', 'Event Details & Analytics')

@section('content')
<div class="space-y-6">

    <!-- Top Event Command Banner -->
    <div class="bg-gradient-to-r from-[#0a1c3d] via-[#132f66] to-[#0a1c3d] text-white rounded-2xl p-5 sm:p-6 shadow-md flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div class="flex items-start gap-4 min-w-0">
            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}"
                class="w-20 h-16 sm:w-24 sm:h-20 rounded-xl object-cover border border-white/20 shrink-0">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-wider">
                    <a href="{{ route('admin.events.index') }}" class="text-blue-300 hover:underline">&larr; All Events</a>
                    <span class="px-2 py-0.5 rounded-full {{ $event->is_published ? 'bg-emerald-500 text-slate-950' : 'bg-slate-600 text-white' }}">
                        {{ $event->is_published ? 'Published' : 'Draft' }}
                    </span>
                    @if($event->is_featured)
                        <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950">Featured</span>
                    @endif
                    @if($event->isPast())
                        <span class="px-2 py-0.5 rounded-full bg-red-500/80 text-white">Past Event</span>
                    @endif
                </div>
                <h2 class="text-xl sm:text-2xl font-black font-headline mt-1 truncate">{{ $event->title }}</h2>
                <p class="text-xs text-blue-100 mt-0.5">
                    {{ $event->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }} EAT &bull; {{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0 text-xs">
            <a href="{{ route('admin.events.tickets', $event) }}"
                class="px-3.5 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black uppercase tracking-wider">
                Gate Check-In &amp; Orders
            </a>
            <a href="{{ route('admin.events.edit', $event) }}"
                class="px-3.5 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold uppercase tracking-wider">
                Edit Event
            </a>
            <a href="{{ route('events.show', $event->slug) }}" target="_blank"
                class="px-3.5 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-blue-200 font-bold uppercase tracking-wider">
                Public Page &nearr;
            </a>
        </div>
    </div>

    <!-- KPI Analytics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Revenue</div>
            <div class="text-xl font-black text-emerald-700 mt-1">{{ $event->currency }} {{ number_format($metrics['revenue'], 0) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $metrics['paid_orders'] }} paid orders</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tickets Sold</div>
            <div class="text-xl font-black text-[#1b52bc] mt-1">
                {{ number_format($metrics['tickets_issued']) }}
                @if($metrics['capacity'])
                    <span class="text-xs font-semibold text-slate-400">/ {{ number_format($metrics['capacity']) }}</span>
                @endif
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $metrics['pending_orders'] }} pending orders</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Gate Check-Ins</div>
            <div class="text-xl font-black text-slate-950 mt-1">{{ number_format($metrics['checked_in']) }}</div>
            <div class="text-[10px] text-emerald-600 font-bold mt-0.5">{{ $metrics['check_in_rate'] }}% attendance</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Unique Buyers</div>
            <div class="text-xl font-black text-slate-950 mt-1">{{ number_format($metrics['unique_attendees']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Auto-linked accounts</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">SMS Sent</div>
            <div class="text-xl font-black text-slate-950 mt-1">{{ number_format($metrics['sms_delivered']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">of {{ $metrics['paid_orders'] }} orders</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Emails Sent</div>
            <div class="text-xl font-black text-slate-950 mt-1">{{ number_format($metrics['email_delivered']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">of {{ $metrics['paid_orders'] }} orders</div>
        </div>
    </div>

    <!-- Ticket Tier Revenue Breakdown -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Ticket Tiers &amp; Revenue Breakdown</h3>
            <span class="text-[11px] text-slate-500">{{ $tierBreakdown->count() }} tiers</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Tier</th>
                        <th class="px-4 py-2.5 text-left">Unit Price</th>
                        <th class="px-4 py-2.5 text-left">Sold / Capacity</th>
                        <th class="px-4 py-2.5 text-left">Progress</th>
                        <th class="px-4 py-2.5 text-right">Tier Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tierBreakdown as $tier)
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $tier['name'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ $tier['price'] > 0 ? $event->currency.' '.number_format($tier['price'], 0) : 'Free' }}</td>
                            <td class="px-4 py-3 font-mono font-bold">
                                {{ $tier['sold'] }} {{ $tier['capacity'] ? '/ '.$tier['capacity'] : '(Unlimited)' }}
                            </td>
                            <td class="px-4 py-3 w-48">
                                @if($tier['percent'] !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $tier['percent'] }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-500">{{ $tier['percent'] }}%</span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400">Open capacity</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-black text-emerald-700">
                                {{ $event->currency }} {{ number_format($tier['revenue'], 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendees & Auto-Created User Accounts -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Event Attendees &amp; Linked User Accounts</h3>
            <span class="text-[11px] text-slate-500">{{ $attendees->count() }} unique buyers</span>
        </div>

        @if($attendees->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No paid attendees yet for this event.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Attendee / Account</th>
                            <th class="px-4 py-2.5 text-left">Contact (SMS / Email)</th>
                            <th class="px-4 py-2.5 text-left">Tickets</th>
                            <th class="px-4 py-2.5 text-left">Delivery</th>
                            <th class="px-4 py-2.5 text-right">Total Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($attendees as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $row['name'] }}</div>
                                    @if($row['user'])
                                        <div class="text-[10px] font-mono text-[#1b52bc]">User #{{ $row['user']->id }} ({{ '@' . $row['user']->username }})</div>
                                    @else
                                        <div class="text-[10px] text-slate-400">Email-only guest</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[11px] text-slate-600">
                                    @if($row['phone'])
                                        <div class="font-mono font-semibold text-slate-800">{{ $row['phone'] }}</div>
                                    @endif
                                    @if($row['email'] && ! str_ends_with($row['email'], '@guests.kisiitv.co.ke'))
                                        <div>{{ $row['email'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-900">
                                    {{ $row['tickets_count'] }} {{ $row['tickets_count'] === 1 ? 'pass' : 'passes' }}
                                    <span class="text-[10px] text-slate-400 font-normal">({{ $row['orders_count'] }} {{ $row['orders_count'] === 1 ? 'order' : 'orders' }})</span>
                                </td>
                                <td class="px-4 py-3 text-[10px] space-x-1">
                                    <span class="px-2 py-0.5 rounded font-bold {{ $row['sms_sent'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400' }}">
                                        SMS {{ $row['sms_sent'] ? '✓' : '—' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded font-bold {{ $row['email_sent'] ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-400' }}">
                                        Email {{ $row['email_sent'] ? '✓' : '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-black text-emerald-700">
                                    {{ $event->currency }} {{ number_format($row['total_spent'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
