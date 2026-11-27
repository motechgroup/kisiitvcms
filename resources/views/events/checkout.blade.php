@extends('layouts.news')

@php $title = 'Complete Ticket Payment — ' . $order->event->title; @endphp

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-10 px-3 sm:px-6">
    <div class="max-w-lg mx-auto bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-slate-950 px-6 py-4 border-b border-slate-800">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Ticket Checkout</span>
            <h1 class="text-lg font-black font-headline text-white mt-0.5">{{ $order->event->title }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">Order Ref: <span class="font-mono text-white">{{ $order->order_reference }}</span></p>
        </div>

        <div class="p-6 space-y-5 text-xs">
            @if(session('error'))
                <div class="p-3.5 rounded-xl bg-red-950/80 border border-red-500/40 text-red-100">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">Attendee</span>
                    <span class="font-bold text-white">{{ $order->buyer_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Ticket Tier</span>
                    <span class="font-bold text-white">{{ $order->ticketType->name }} (&times;{{ $order->quantity }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Delivery</span>
                    <span class="font-semibold text-emerald-400">{{ $order->buyer_phone }} &amp; {{ $order->buyer_email }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-800 text-sm">
                    <span class="font-bold text-slate-300">Total</span>
                    <span class="font-mono font-black text-emerald-400">{{ $order->currency }} {{ number_format((float) $order->total_amount, 0) }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('events.checkout.complete', $order->order_reference) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                        Payment / M-Pesa Confirmation Code (Optional)
                    </label>
                    <input type="text" name="external_reference" placeholder="e.g. UBA48K92P1"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-emerald-500">
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black uppercase tracking-wider rounded-xl transition cursor-pointer">
                    Confirm Payment &amp; Issue Ticket &rarr;
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
