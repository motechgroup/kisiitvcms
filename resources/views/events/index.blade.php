@extends('layouts.news')

@php
    $title = 'Kisii TV Events — Upcoming & Past Live Experiences';
    $metaDescription = 'Discover upcoming and past Kisii TV live events, cultural festivals, town halls, and concerts. Book digital tickets with instant SMS and email delivery.';
@endphp

@section('content')
<div class="bg-slate-950 text-white min-h-screen">
    <!-- Modern Atmospheric Hero Header -->
    <div class="relative overflow-hidden border-b border-slate-800/80 bg-gradient-to-br from-[#071329] via-[#0d2452] to-slate-950">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-8 sm:py-12">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="max-w-2xl space-y-2.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[11px] font-bold uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Kisii TV Live Experiences</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black font-headline tracking-tight text-white leading-tight">
                        Events &amp; <span class="text-emerald-400">Digital Tickets</span>
                    </h1>
                    <p class="text-xs sm:text-base text-slate-300 leading-relaxed">
                        Concerts, county town halls, cultural festivals, and live broadcasts. Book in seconds — no signup needed. Your pass is delivered instantly via SMS or Email.
                    </p>
                </div>

                <!-- Search Bar -->
                <form method="GET" action="{{ route('events.index') }}" class="w-full lg:w-80 flex items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Search events, venue, city..."
                            class="w-full bg-slate-900/90 border border-slate-700 focus:border-emerald-400 rounded-xl pl-3.5 pr-9 py-2.5 text-xs sm:text-sm text-white placeholder-slate-400 focus:outline-none">
                        @if($search !== '')
                            <a href="{{ route('events.index', ['tab' => $tab]) }}"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-sm font-bold">&times;</a>
                        @endif
                    </div>
                    <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer shrink-0">
                        Find
                    </button>
                </form>
            </div>

            <!-- Upcoming / Past Tabs -->
            <div class="mt-6 flex items-center gap-2 border-t border-slate-800/80 pt-5">
                <a href="{{ route('events.index', array_filter(['tab' => 'upcoming', 'q' => $search])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition flex items-center gap-2 {{ $tab === 'upcoming' ? 'bg-emerald-500 text-slate-950 shadow-lg shadow-emerald-500/20' : 'bg-slate-900/80 text-slate-300 border border-slate-800 hover:border-slate-600' }}">
                    <span>Upcoming Events</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono {{ $tab === 'upcoming' ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-300' }}">
                        {{ $upcomingCount }}
                    </span>
                </a>

                <a href="{{ route('events.index', array_filter(['tab' => 'past', 'q' => $search])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition flex items-center gap-2 {{ $tab === 'past' ? 'bg-white text-slate-950 shadow-lg' : 'bg-slate-900/80 text-slate-300 border border-slate-800 hover:border-slate-600' }}">
                    <span>Past Events</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono {{ $tab === 'past' ? 'bg-slate-950/15 text-slate-950' : 'bg-slate-800 text-slate-300' }}">
                        {{ $pastCount }}
                    </span>
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-7 sm:py-10 space-y-10">

        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-950/80 border border-red-500/40 text-red-100 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        @if($events->isEmpty())
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-12 text-center space-y-2">
                <h2 class="text-lg font-bold text-white font-headline">
                    {{ $tab === 'past' ? 'No Past Events Found' : 'No Upcoming Events Found' }}
                </h2>
                <p class="text-xs text-slate-400">
                    @if($search !== '')
                        No events matched "{{ $search }}". Try a different keyword or switch tabs.
                    @else
                        Check back soon for new Kisii TV live shows, community forums, and festivals.
                    @endif
                </p>
                @if($tab === 'upcoming' && $pastCount > 0)
                    <a href="{{ route('events.index', ['tab' => 'past']) }}"
                        class="inline-block mt-3 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider transition">
                        Browse Past Events &rarr;
                    </a>
                @endif
            </div>
        @else
            @php
                $showSpotlightHero = $tab === 'upcoming' && $events->currentPage() === 1 && $search === '';
                $heroEvent = $showSpotlightHero ? $events->first() : null;
                $gridEvents = $showSpotlightHero ? $events->skip(1) : $events;
            @endphp

            <!-- Spotlight Featured Upcoming Event -->
            @if($heroEvent)
                @php $heroMin = $heroEvent->startingPrice(); @endphp
                <a href="{{ route('events.show', $heroEvent->slug) }}"
                    class="group block relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 hover:border-emerald-500/60 shadow-2xl transition">
                    <div class="grid grid-cols-1 lg:grid-cols-12">
                        <div class="lg:col-span-7 relative aspect-[16/10] lg:aspect-auto lg:min-h-[360px] overflow-hidden bg-slate-950">
                            <img src="{{ $heroEvent->coverImageUrl() }}" alt="{{ $heroEvent->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent lg:hidden"></div>
                            <div class="absolute top-4 left-4 flex items-center gap-2">
                                <span class="bg-emerald-500 text-slate-950 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow">
                                    Featured Event
                                </span>
                                <span class="bg-slate-950/85 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-full border border-white/10">
                                    {{ $heroEvent->starts_at->timezone('Africa/Nairobi')->format('M j, Y') }}
                                </span>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 p-5 lg:hidden">
                                <div class="text-xs font-semibold text-emerald-300 mb-1">
                                    {{ $heroEvent->venue }}{{ $heroEvent->city ? ', '.$heroEvent->city : '' }}
                                </div>
                                <h2 class="text-2xl font-black text-white font-headline leading-tight">{{ $heroEvent->title }}</h2>
                                <div class="mt-3 inline-flex items-center gap-2 bg-emerald-500 text-slate-950 text-xs font-black uppercase px-4 py-2 rounded-xl">
                                    <span>{{ $heroMin !== null && $heroMin > 0 ? 'From '.$heroEvent->currency.' '.number_format($heroMin, 0) : 'Free Entry' }}</span>
                                    <span>&rarr;</span>
                                </div>
                            </div>
                        </div>

                        <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-7 bg-gradient-to-b from-slate-900 to-slate-950">
                            <div class="space-y-3">
                                <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-400">
                                    {{ $heroEvent->starts_at->timezone('Africa/Nairobi')->format('l, F j, Y · g:i A') }} EAT
                                </div>
                                <h2 class="text-3xl font-black text-white group-hover:text-emerald-300 transition font-headline leading-tight">
                                    {{ $heroEvent->title }}
                                </h2>
                                <div class="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
                                    <span>📍</span>
                                    <span>{{ $heroEvent->venue }}{{ $heroEvent->city ? ', '.$heroEvent->city : '' }}</span>
                                </div>
                                @if($heroEvent->summary)
                                    <p class="text-sm text-slate-400 leading-relaxed line-clamp-3">{{ $heroEvent->summary }}</p>
                                @endif
                            </div>

                            <div class="pt-5 mt-6 border-t border-slate-800 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Starting From</div>
                                    <div class="text-xl font-mono font-black text-emerald-400">
                                        {{ $heroMin !== null && $heroMin > 0 ? $heroEvent->currency.' '.number_format($heroMin, 0) : 'FREE' }}
                                    </div>
                                </div>
                                <span class="px-5 py-3 bg-emerald-500 group-hover:bg-emerald-400 text-slate-950 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-500/20 transition">
                                    Get Tickets &rarr;
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endif

            <!-- Responsive Modern Event Cards Grid -->
            @if($gridEvents->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($gridEvents as $event)
                        @php
                            $minPrice = $event->startingPrice();
                            $ended = $event->isPast();
                        @endphp
                        <a href="{{ route('events.show', $event->slug) }}"
                            class="group bg-slate-900 rounded-2xl border border-slate-800 hover:border-emerald-500/50 overflow-hidden shadow-lg transition flex flex-col justify-between">
                            <div>
                                <div class="relative aspect-[16/10] overflow-hidden bg-slate-950">
                                    <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500 {{ $ended ? 'opacity-65 grayscale-[30%]' : '' }}">
                                    <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-950/90 backdrop-blur text-white text-[10px] font-black uppercase border border-white/10">
                                            {{ $event->starts_at->timezone('Africa/Nairobi')->format('M j, Y') }}
                                        </span>
                                        @if($ended)
                                            <span class="px-2 py-1 rounded-lg bg-slate-700/90 text-slate-200 text-[10px] font-bold uppercase">
                                                Past Event
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-4 sm:p-5 space-y-2">
                                    <div class="text-[11px] font-semibold text-emerald-400">
                                        {{ $event->starts_at->timezone('Africa/Nairobi')->format('D · g:i A') }} EAT
                                    </div>
                                    <h3 class="text-base sm:text-lg font-black text-white group-hover:text-emerald-300 transition font-headline leading-snug line-clamp-2">
                                        {{ $event->title }}
                                    </h3>
                                    <div class="text-xs text-slate-400 truncate">
                                        📍 {{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 sm:px-5 py-3.5 border-t border-slate-800/80 flex items-center justify-between text-xs bg-slate-950/40">
                                <span class="font-mono font-black {{ $ended ? 'text-slate-400' : 'text-emerald-400' }}">
                                    @if($ended)
                                        Event Ended
                                    @else
                                        {{ $minPrice !== null && $minPrice > 0 ? 'From '.$event->currency.' '.number_format($minPrice, 0) : 'Free Entry' }}
                                    @endif
                                </span>
                                <span class="font-bold text-white group-hover:text-emerald-300 transition">
                                    {{ $ended ? 'View Details' : 'Book Ticket' }} &rarr;
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Pagination -->
            @if($events->hasPages())
                <div class="pt-4 border-t border-slate-800">
                    {{ $events->links() }}
                </div>
            @endif
        @endif

        <!-- Compact Past Events Showcase below Upcoming Tab -->
        @if($recentPastEvents->isNotEmpty())
            <section class="pt-8 border-t border-slate-800/80 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Archive</span>
                        <h2 class="text-xl font-black font-headline text-white">Recent Past Events</h2>
                    </div>
                    <a href="{{ route('events.index', ['tab' => 'past']) }}"
                        class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:underline">
                        View All Past Events ({{ $pastCount }}) &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach($recentPastEvents as $past)
                        <a href="{{ route('events.show', $past->slug) }}"
                            class="group flex items-center gap-3.5 bg-slate-900/70 border border-slate-800 hover:border-slate-600 rounded-2xl p-3 transition">
                            <img src="{{ $past->coverImageUrl() }}" alt="{{ $past->title }}" loading="lazy"
                                class="w-20 h-16 rounded-xl object-cover opacity-75 shrink-0">
                            <div class="min-w-0">
                                <div class="text-[10px] font-bold uppercase text-slate-400">
                                    {{ $past->starts_at->timezone('Africa/Nairobi')->format('M j, Y') }}
                                </div>
                                <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition truncate mt-0.5">
                                    {{ $past->title }}
                                </h3>
                                <div class="text-[11px] text-slate-400 truncate">{{ $past->venue }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
