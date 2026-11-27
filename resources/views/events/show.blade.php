@extends('layouts.news')

@php
    $title = $event->title . ' — Tickets | Kisii TV Events';
    $metaDescription = $event->summary ?: ('Get digital tickets for ' . $event->title . ' at ' . $event->venue . '. Instant SMS and email ticket delivery.');
    $tiersJson = $event->activeTicketTypes->map(fn ($t) => [
        'id' => $t->id,
        'name' => $t->name,
        'price' => (float) $t->price,
        'sold_out' => $t->isSoldOut(),
    ])->values()->all();
    $firstAvailableTier = $event->activeTicketTypes->first(fn ($t) => ! $t->isSoldOut()) ?? $event->activeTicketTypes->first();
    $defaultGateway = $activeGateways->first()?->code ?? 'paystack';
    $isPastEvent = $event->isPast();
@endphp

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-6 sm:py-10">
    <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8">

        <div class="mb-5 flex items-center justify-between">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>&larr;</span>
                <span>Back to Events</span>
            </a>
            @if($isPastEvent)
                <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-[10px] font-black uppercase tracking-wider">
                    Past Event
                </span>
            @endif
        </div>

        @if(session('error'))
            <div class="mb-5 p-4 rounded-xl bg-red-950/80 border border-red-500/40 text-red-100 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-950/80 border border-red-500/40 text-red-100 text-xs space-y-1">
                <div class="font-bold uppercase">Please check your details:</div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- Left Column: Modern Event Hero, Details & Flyer (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                <div class="rounded-3xl overflow-hidden border border-slate-800 bg-slate-900 shadow-2xl">
                    <div class="relative aspect-[16/10] bg-slate-950">
                        <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/35 to-transparent"></div>
                        <div class="absolute bottom-5 left-5 right-5">
                            <span class="inline-flex items-center gap-1.5 bg-emerald-500 text-slate-950 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full mb-2.5 shadow">
                                {{ $event->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }} EAT
                            </span>
                            <h1 class="text-2xl sm:text-4xl font-black font-headline text-white leading-tight">
                                {{ $event->title }}
                            </h1>
                        </div>
                    </div>

                    <div class="p-5 sm:p-7 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Date &amp; Time</div>
                                <div class="font-bold text-white text-sm mt-1">{{ $event->starts_at->timezone('Africa/Nairobi')->format('l, F j, Y') }}</div>
                                <div class="text-slate-400 mt-0.5">
                                    {{ $event->starts_at->timezone('Africa/Nairobi')->format('g:i A') }}
                                    @if($event->ends_at)
                                        &ndash; {{ $event->ends_at->timezone('Africa/Nairobi')->format('g:i A') }}
                                    @endif
                                    EAT
                                </div>
                            </div>
                            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Location</div>
                                <div class="font-bold text-white text-sm mt-1">{{ $event->venue }}</div>
                                @if($event->city)
                                    <div class="text-slate-400 mt-0.5">{{ $event->city }}</div>
                                @endif
                            </div>
                        </div>

                        @if($event->summary)
                            <p class="text-sm sm:text-base text-slate-200 font-medium leading-relaxed">{{ $event->summary }}</p>
                        @endif

                        @if($event->description)
                            <div class="text-xs sm:text-sm text-slate-300 leading-relaxed whitespace-pre-line pt-4 border-t border-slate-800">
                                {{ $event->description }}
                            </div>
                        @endif

                        @if($flyerUrl = $event->flyerImageUrl())
                            <div class="pt-5 border-t border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Official Event Flyer</span>
                                    <a href="{{ $flyerUrl }}" target="_blank" rel="noopener" class="text-xs font-bold text-emerald-400 hover:underline">
                                        Open Full Size &nearr;
                                    </a>
                                </div>
                                <a href="{{ $flyerUrl }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
                                    <img src="{{ $flyerUrl }}" alt="{{ $event->title }} Flyer" class="w-full max-h-[520px] object-contain mx-auto">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Frictionless Ticket Checkout Card (5 cols) -->
            <div class="lg:col-span-5 lg:sticky lg:top-6 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden"
                x-data="{
                    tiers: @js($tiersJson),
                    selectedTierId: {{ (int) old('event_ticket_type_id', $firstAvailableTier?->id ?? 0) }},
                    quantity: {{ (int) old('quantity', 1) }},
                    gateway: '{{ old('gateway_code', $defaultGateway) }}',
                    deliveryMode: '{{ old('buyer_email') && old('buyer_phone') ? 'both' : (old('buyer_email') && ! old('buyer_phone') ? 'email' : 'phone') }}',
                    buyerPhone: @js((string) old('buyer_phone', auth()->user()?->phone ?? '')),
                    buyerEmail: @js((string) old('buyer_email', auth()->user()?->email ?? '')),
                    buyerName: @js((string) old('buyer_name', auth()->user()?->name ?? '')),
                    recognizedName: '',
                    lookupTimer: null,
                    get activeTier() {
                        return this.tiers.find(t => t.id == this.selectedTierId) || this.tiers[0] || { price: 0, name: '' };
                    },
                    get totalAmount() {
                        return (Number(this.activeTier.price || 0) * Number(this.quantity || 1));
                    },
                    scheduleLookup() {
                        clearTimeout(this.lookupTimer);
                        const digits = (this.buyerPhone || '').replace(/\D/g, '');
                        if (digits.length < 9) {
                            this.recognizedName = '';
                            return;
                        }
                        this.lookupTimer = setTimeout(async () => {
                            try {
                                const res = await fetch(`{{ route('events.lookup-attendee') }}?phone=${encodeURIComponent(this.buyerPhone)}`);
                                const data = await res.json();
                                if (data.found) {
                                    this.recognizedName = data.name || '';
                                    if (!this.buyerName && data.name) this.buyerName = data.name;
                                    if (!this.buyerEmail && data.email) this.buyerEmail = data.email;
                                } else {
                                    this.recognizedName = '';
                                }
                            } catch (e) {}
                        }, 350);
                    }
                }">
                <div class="bg-gradient-to-r from-[#0a1c3d] to-slate-950 px-6 py-4 border-b border-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-black font-headline text-white">Get Your Tickets</h2>
                            <p class="text-[11px] text-emerald-400 mt-0.5">
                                No signup needed &bull; Instant digital pass
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[10px] font-bold uppercase">
                            Secure
                        </span>
                    </div>
                </div>

                @if($isPastEvent)
                    <div class="p-6 text-center space-y-2">
                        <div class="text-sm font-bold text-white">This event has ended</div>
                        <p class="text-xs text-slate-400">Ticket sales are closed. Browse upcoming Kisii TV events instead.</p>
                        <a href="{{ route('events.index') }}"
                            class="inline-block mt-2 px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-black uppercase">
                            View Upcoming Events &rarr;
                        </a>
                    </div>
                @elseif($event->activeTicketTypes->isEmpty())
                    <div class="p-6 text-center text-xs text-slate-400">
                        Ticket sales are not open for this event yet.
                    </div>
                @else
                    <form method="POST" action="{{ route('events.purchase', $event->slug) }}" class="p-5 sm:p-6 space-y-5 text-xs">
                        @csrf

                        <!-- 1. Ticket Tier -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                1. Choose Ticket Tier
                            </label>
                            <div class="space-y-2">
                                @foreach($event->activeTicketTypes as $tier)
                                    @php $soldOut = $tier->isSoldOut(); @endphp
                                    <label @if(! $soldOut) @click="selectedTierId = {{ $tier->id }}" @endif
                                        :class="selectedTierId == {{ $tier->id }} ? 'border-emerald-400 bg-emerald-950/20 ring-1 ring-emerald-400/50' : 'border-slate-800 bg-slate-950/70 hover:border-slate-600'"
                                        class="flex items-center justify-between gap-3 p-3.5 rounded-2xl border-2 transition {{ $soldOut ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <input type="radio" name="event_ticket_type_id" value="{{ $tier->id }}"
                                                x-model="selectedTierId" {{ $soldOut ? 'disabled' : '' }}
                                                class="mt-1 text-emerald-500 bg-slate-900 border-slate-700">
                                            <div class="min-w-0">
                                                <div class="font-bold text-white text-sm">{{ $tier->name }}</div>
                                                @if($tier->description)
                                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $tier->description }}</div>
                                                @endif
                                                @if($tier->remaining() !== null)
                                                    <div class="text-[10px] font-semibold mt-1 {{ $soldOut ? 'text-red-400' : 'text-slate-400' }}">
                                                        {{ $soldOut ? 'Sold Out' : $tier->remaining().' tickets remaining' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-mono font-black text-sm text-emerald-400">
                                                {{ (float) $tier->price > 0 ? $event->currency.' '.number_format((float) $tier->price, 0) : 'FREE' }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- 2. Quantity -->
                        <div class="flex items-center justify-between bg-slate-950/80 border border-slate-800 rounded-2xl px-4 py-3">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-300">Quantity</div>
                                <div class="text-[11px] text-slate-500">Number of passes</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="if (quantity > 1) quantity--"
                                    class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-black text-base cursor-pointer">&minus;</button>
                                <input type="number" name="quantity" x-model.number="quantity" min="1" max="20" readonly
                                    class="w-10 text-center bg-transparent text-white font-mono font-black text-base focus:outline-none">
                                <button type="button" @click="if (quantity < 20) quantity++"
                                    class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-black text-base cursor-pointer">+</button>
                            </div>
                        </div>

                        <!-- 3. Where to Send Ticket (Phone OR Email OR Both) -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    2. Where Should We Send Your Ticket?
                                </label>
                            </div>

                            <!-- Delivery Mode Pills -->
                            <div class="grid grid-cols-3 gap-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800">
                                <button type="button" @click="deliveryMode = 'phone'"
                                    :class="deliveryMode === 'phone' ? 'bg-emerald-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-semibold'"
                                    class="py-1.5 rounded-lg text-[11px] transition cursor-pointer">
                                    SMS (Phone)
                                </button>
                                <button type="button" @click="deliveryMode = 'email'"
                                    :class="deliveryMode === 'email' ? 'bg-emerald-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-semibold'"
                                    class="py-1.5 rounded-lg text-[11px] transition cursor-pointer">
                                    Email Only
                                </button>
                                <button type="button" @click="deliveryMode = 'both'"
                                    :class="deliveryMode === 'both' ? 'bg-emerald-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-semibold'"
                                    class="py-1.5 rounded-lg text-[11px] transition cursor-pointer">
                                    Both
                                </button>
                            </div>

                            <!-- Phone Input -->
                            <div x-show="deliveryMode === 'phone' || deliveryMode === 'both'">
                                <input type="tel" name="buyer_phone" x-model="buyerPhone" @input="scheduleLookup()"
                                    :required="deliveryMode === 'phone' || deliveryMode === 'both'"
                                    placeholder="Phone Number for SMS (e.g. 0712345678)"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none">
                                <p x-show="recognizedName" x-cloak class="text-[11px] text-emerald-400 font-semibold mt-1">
                                    Welcome back, <span x-text="recognizedName"></span>! Your details are remembered.
                                </p>
                            </div>

                            <!-- Email Input -->
                            <div x-show="deliveryMode === 'email' || deliveryMode === 'both'" x-cloak>
                                <input type="email" name="buyer_email" x-model="buyerEmail"
                                    :required="deliveryMode === 'email' || deliveryMode === 'both'"
                                    placeholder="Email Address (e.g. you@example.com)"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none">
                            </div>

                            <!-- Optional Name -->
                            <div>
                                <input type="text" name="buyer_name" x-model="buyerName"
                                    placeholder="Your Name (Optional — printed on pass)"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none">
                            </div>
                        </div>

                        <!-- 4. Payment Gateway (Only when Total > 0) -->
                        <div x-show="totalAmount > 0" x-cloak class="space-y-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                3. Payment Method
                            </label>
                            <div class="grid gap-2.5 {{ $activeGateways->count() === 1 ? 'grid-cols-1 max-w-[240px] mx-auto' : 'grid-cols-2' }}">
                                @foreach($activeGateways as $gw)
                                    @php
                                        $label = match ($gw->code) {
                                            'mpesa' => 'Mobile Money',
                                            'paystack' => 'Card, Apple Pay & More',
                                            default => $gw->currency,
                                        };
                                    @endphp
                                    <label @click="gateway = '{{ $gw->code }}'"
                                        :class="gateway === '{{ $gw->code }}' ? 'border-emerald-400 ring-2 ring-emerald-400/40' : 'border-slate-800 opacity-80 hover:opacity-100'"
                                        class="relative border-2 rounded-xl overflow-hidden cursor-pointer transition bg-slate-950 flex flex-col">
                                        <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="gateway" class="sr-only">
                                        <div class="h-14 w-full flex items-center justify-center overflow-hidden">
                                            @if($gwLogo = $gw->logoUrl())
                                                <img src="{{ $gwLogo }}" alt="{{ $gw->name }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="font-black text-white uppercase">{{ ucfirst($gw->code) }}</span>
                                            @endif
                                        </div>
                                        <div class="px-2 py-1.5 text-center border-t border-slate-800">
                                            <span class="text-[10px] font-semibold text-slate-200 block truncate">{{ $label }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Total & Submit -->
                        <div class="pt-3 border-t border-slate-800 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-400 font-semibold">Total Payable</span>
                                <span class="font-mono font-black text-lg text-white">
                                    <span x-show="totalAmount > 0">{{ $event->currency }} <span x-text="totalAmount.toLocaleString()"></span></span>
                                    <span x-show="totalAmount <= 0" x-cloak class="text-emerald-400">FREE</span>
                                </span>
                            </div>

                            <button type="submit"
                                class="w-full py-3.5 px-5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-500/20 transition cursor-pointer">
                                <span x-show="totalAmount <= 0" x-cloak>Confirm Free Ticket &rarr;</span>
                                <span x-show="totalAmount > 0">Pay &amp; Get Ticket Link &rarr;</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
