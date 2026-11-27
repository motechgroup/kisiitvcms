@extends('layouts.admin')

@section('title', 'Event Tickets & Check-In')
@section('page_title', 'Tickets & Gate Check-In — '.$event->title)

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 rounded-xl shadow-2xs">
        <div>
            <a href="{{ route('admin.events.index') }}" class="text-[11px] font-bold text-[#1b52bc] hover:underline">&larr; Back to Events</a>
            <h2 class="text-base font-bold text-slate-950 font-headline mt-1">{{ $event->title }}</h2>
            <p class="text-xs text-slate-500">
                {{ $event->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }} &bull; {{ $event->venue }}
            </p>
        </div>

        <form method="GET" action="{{ route('admin.events.tickets', $event) }}" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search code, name, phone, email..."
                class="border border-slate-300 rounded-lg px-3 py-2 text-xs w-60 focus:outline-none focus:border-[#1b52bc]">
            <button type="submit" class="px-4 py-2 bg-[#0a1c3d] text-white text-xs font-bold uppercase rounded-lg cursor-pointer">
                Search
            </button>
        </form>
    </div>

    <!-- Issued Digital Tickets (Gate Check-In) -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Issued Digital Tickets (Gate Check-In)</h3>
            <span class="text-[11px] text-slate-500 font-semibold">{{ $tickets->total() }} total passes</span>
        </div>

        @if($tickets->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No issued tickets match your filter yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Ticket Code</th>
                            <th class="px-4 py-2.5 text-left">Attendee</th>
                            <th class="px-4 py-2.5 text-left">Tier</th>
                            <th class="px-4 py-2.5 text-left">Status</th>
                            <th class="px-4 py-2.5 text-right">Gate Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-slate-950">
                                    {{ $ticket->ticket_code }}
                                    <div>
                                        <a href="{{ $ticket->passUrl() }}" target="_blank" class="text-[10px] text-[#1b52bc] hover:underline font-sans font-semibold">
                                            Open Pass &nearr;
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $ticket->attendee_name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $ticket->attendee_phone }} &bull; {{ $ticket->attendee_email }}</div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ $ticket->ticketType->name }}</td>
                                <td class="px-4 py-3">
                                    @if($ticket->status === 'checked_in')
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-bold uppercase">
                                            Checked In · {{ $ticket->checked_in_at?->timezone('Africa/Nairobi')->format('g:i A') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase">
                                            Valid Pass
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($ticket->status !== 'checked_in')
                                        <form method="POST" action="{{ route('admin.events.tickets.check-in', $ticket) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] cursor-pointer">
                                                Check In Guest
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-semibold">Admitted</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $tickets->links() }}</div>
        @endif
    </div>

    <!-- Orders & Delivery Status -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Ticket Orders &amp; SMS / Email Delivery</h3>
            <span class="text-[11px] text-slate-500 font-semibold">{{ $orders->total() }} orders</span>
        </div>

        @if($orders->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No orders placed for this event yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Order Ref</th>
                            <th class="px-4 py-2.5 text-left">Buyer</th>
                            <th class="px-4 py-2.5 text-left">Tickets &amp; Total</th>
                            <th class="px-4 py-2.5 text-left">Payment</th>
                            <th class="px-4 py-2.5 text-left">SMS &amp; Email</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $order->order_reference }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $order->buyer_name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $order->buyer_phone }} &bull; {{ $order->buyer_email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold">{{ $order->quantity }} &times; {{ $order->ticketType->name }}</div>
                                    <div class="text-[11px] text-emerald-700 font-semibold">{{ $order->currency }} {{ number_format((float) $order->total_amount, 0) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $order->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $order->status }} ({{ $order->gateway_code }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[11px]">
                                    <div>Email: <span class="font-bold {{ $order->email_sent_at ? 'text-emerald-700' : 'text-slate-400' }}">{{ $order->email_sent_at ? 'Sent' : 'Pending' }}</span></div>
                                    <div>SMS: <span class="font-bold {{ $order->sms_sent_at ? 'text-emerald-700' : 'text-slate-400' }}">{{ $order->sms_sent_at ? 'Sent' : 'Pending' }}</span></div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($order->status === 'paid')
                                        <form method="POST" action="{{ route('admin.events.orders.resend', $order) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-[#1b52bc] hover:bg-blue-800 text-white font-bold text-[11px] cursor-pointer">
                                                Resend SMS &amp; Email
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.events.orders.mark-paid', $order) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] cursor-pointer">
                                                Mark Paid &amp; Send Ticket
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
