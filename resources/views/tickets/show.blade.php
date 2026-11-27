@extends('layouts.news')

@php
    $title = 'Digital Ticket Pass — ' . $ticket->event->title;
    $qrData = urlencode($ticket->ticket_code . ' | ' . $ticket->passUrl());
@endphp

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-8 sm:py-12 px-3 sm:px-6">
    <div class="max-w-md mx-auto space-y-5">

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-950/90 border border-emerald-500/50 text-emerald-100 text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <!-- Digital Boarding-Pass Style Ticket -->
        <div class="bg-white text-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-200">
            <!-- Top Event Cover & Status -->
            <div class="relative h-40 bg-slate-900">
                <img src="{{ $ticket->event->coverImageUrl() }}" alt="{{ $ticket->event->title }}" class="w-full h-full object-cover opacity-80">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                <div class="absolute top-3 left-4 right-4 flex items-center justify-between">
                    <span class="bg-[#0a1c3d]/90 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                        Kisii TV Pass
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $ticket->status === 'checked_in' ? 'bg-blue-500 text-white' : 'bg-emerald-500 text-slate-950' }}">
                        {{ $ticket->status === 'checked_in' ? 'Checked In' : 'Valid Ticket' }}
                    </span>
                </div>
                <div class="absolute bottom-3 left-4 right-4">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">{{ $ticket->ticketType->name }}</div>
                    <h1 class="text-lg sm:text-xl font-black font-headline text-white leading-tight">{{ $ticket->event->title }}</h1>
                </div>
            </div>

            <!-- Ticket Details -->
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Date</div>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $ticket->event->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Time</div>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $ticket->event->starts_at->timezone('Africa/Nairobi')->format('g:i A') }} EAT</div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Venue</div>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $ticket->event->venue }}{{ $ticket->event->city ? ', '.$ticket->event->city : '' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attendee</div>
                        <div class="font-bold text-slate-900 mt-0.5 truncate">{{ $ticket->attendee_name }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Phone</div>
                        <div class="font-mono font-bold text-slate-900 mt-0.5">{{ $ticket->attendee_phone }}</div>
                    </div>
                </div>

                <!-- Perforated Tear Line -->
                <div class="relative border-t-2 border-dashed border-slate-200 my-2"></div>

                <!-- QR Code & Ticket Code -->
                <div class="text-center space-y-2.5">
                    <div class="inline-block p-3 bg-white border-2 border-slate-200 rounded-2xl shadow-2xs">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ $qrData }}"
                            alt="QR Code {{ $ticket->ticket_code }}" class="w-40 h-40 mx-auto">
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Ticket Verification Code</div>
                        <div class="text-xl font-mono font-black tracking-widest text-[#0a1c3d] mt-0.5">
                            {{ $ticket->ticket_code }}
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Show this QR code or ticket code at the venue gate for check-in.
                    </p>
                </div>

                @if($siblingTickets->count() > 1)
                    <div class="pt-3 border-t border-slate-200 space-y-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            All Passes In Your Order ({{ $siblingTickets->count() }})
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($siblingTickets as $idx => $sib)
                                <a href="{{ $sib->passUrl() }}"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold transition {{ $sib->id === $ticket->id ? 'bg-[#0a1c3d] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                    #{{ $idx + 1 }} {{ $sib->ticket_code }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="window.print()"
                        class="flex-1 py-2.5 bg-[#0a1c3d] hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition cursor-pointer">
                        Print / Save PDF
                    </button>
                    <a href="{{ route('events.show', $ticket->event->slug) }}"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold uppercase tracking-wider rounded-xl transition">
                        Event Info
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
