@extends('layouts.news')

@php
    $title = 'Kisii TV Events & Digital Tickets';
    $metaDescription = 'Discover upcoming Kisii TV live events, cultural festivals, town halls, and concerts. Buy verified digital tickets with instant SMS and email delivery.';
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-5 sm:py-8">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white rounded-2xl p-5 sm:p-8 mb-6 sm:mb-8 border-b-4 border-[#d9252a] shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-300">Instant SMS &amp; Email Tickets</span>
                <h1 class="text-2xl sm:text-4xl font-black font-headline tracking-tight mt-1">
                    Kisii TV <span class="text-red-400">Events</span> &amp; Live Experiences
                </h1>
                <p class="text-xs sm:text-sm text-slate-200 mt-1.5 max-w-2xl">
                    Book tickets to live concerts, county town halls, cultural festivals, and community galas. Your digital pass is sent straight to your phone and inbox.
                </p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    @if($events->isEmpty())
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-12 text-center">
            <h2 class="text-lg font-bold text-slate-800 font-headline">No Upcoming Events Listed Yet</h2>
            <p class="text-xs text-slate-500 mt-1">Check back soon for upcoming Kisii TV live shows, community forums, and festivals.</p>
        </div>
    @else
        @php
            $heroEvent = $events->first();
            $gridEvents = $events->skip(1);
        @endphp

        <!-- Hero Featured Event -->
        @if($heroEvent)
            @php $heroMin = $heroEvent->startingPrice(); @endphp
            <a href="{{ route('events.show', $heroEvent->slug) }}"
                class="group block relative overflow-hidden rounded-2xl bg-slate-950 border border-slate-200 shadow-md mb-6 sm:mb-8">
                <div class="grid grid-cols-1 md:grid-cols-12">
                    <div class="md:col-span-7 relative aspect-[16/10] sm:aspect-[16/9] overflow-hidden bg-slate-900">
                        <img src="{{ $heroEvent->coverImageUrl() }}" alt="{{ $heroEvent->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/35 to-transparent md:hidden"></div>
                        <span class="absolute top-3 left-3 bg-[#d9252a] text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md shadow">
                            {{ $heroEvent->starts_at->timezone('Africa/Nairobi')->format('M j, Y') }}
                        </span>
                        <div class="absolute inset-x-0 bottom-0 p-4 md:hidden">
                            <div class="text-[11px] font-bold text-emerald-300 mb-1">
                                {{ $heroEvent->venue }}{{ $heroEvent->city ? ', '.$heroEvent->city : '' }}
                            </div>
                            <h2 class="text-xl font-black text-white font-headline leading-tight">{{ $heroEvent->title }}</h2>
                            <div class="mt-2 inline-flex items-center gap-2 bg-emerald-500 text-slate-950 text-[11px] font-black uppercase px-3 py-1.5 rounded-lg">
                                <span>{{ $heroMin !== null && $heroMin > 0 ? 'From '.$heroEvent->currency.' '.number_format($heroMin, 0) : 'Free Entry' }}</span>
                                <span>&rarr;</span>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:flex md:col-span-5 flex-col justify-between bg-white p-6">
                        <div class="space-y-2.5">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-[#1b52bc]">
                                {{ $heroEvent->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }}
                            </div>
                            <h2 class="text-2xl font-black text-slate-950 group-hover:text-[#1b52bc] transition font-headline leading-tight">
                                {{ $heroEvent->title }}
                            </h2>
                            <div class="text-xs font-semibold text-slate-600">
                                📍 {{ $heroEvent->venue }}{{ $heroEvent->city ? ', '.$heroEvent->city : '' }}
                            </div>
                            @if($heroEvent->summary)
                                <p class="text-sm text-slate-600 line-clamp-3 leading-relaxed">{{ $heroEvent->summary }}</p>
                            @endif
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold uppercase text-slate-400">Tickets</div>
                                <div class="text-lg font-black text-emerald-700">
                                    {{ $heroMin !== null && $heroMin > 0 ? 'From '.$heroEvent->currency.' '.number_format($heroMin, 0) : 'Free RSVP' }}
                                </div>
                            </div>
                            <span class="px-4 py-2.5 bg-[#1b52bc] group-hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded-xl transition">
                                Get Tickets &rarr;
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        @endif

        <!-- Events 2-Column Mobile Grid -->
        @if($gridEvents->isNotEmpty())
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                @foreach($gridEvents as $event)
                    @php $minPrice = $event->startingPrice(); @endphp
                    <a href="{{ route('events.show', $event->slug) }}"
                        class="group bg-white rounded-xl border border-slate-200 hover:border-[#1b52bc] overflow-hidden shadow-2xs transition flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-[16/10] overflow-hidden bg-slate-900">
                                <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <span class="absolute top-2 left-2 bg-[#0a1c3d]/90 text-white text-[9px] sm:text-[10px] font-black uppercase px-2 py-0.5 rounded">
                                    {{ $event->starts_at->timezone('Africa/Nairobi')->format('M j') }}
                                </span>
                            </div>
                            <div class="p-3 sm:p-4 space-y-1">
                                <h3 class="text-[13px] sm:text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition font-headline leading-snug line-clamp-2">
                                    {{ $event->title }}
                                </h3>
                                <div class="text-[10px] sm:text-xs text-slate-500 truncate">
                                    {{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="px-3 sm:px-4 pb-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] sm:text-xs">
                            <span class="font-black text-emerald-700">
                                {{ $minPrice !== null && $minPrice > 0 ? $event->currency.' '.number_format($minPrice, 0) : 'Free' }}
                            </span>
                            <span class="font-bold text-[#1b52bc]">Book &rarr;</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-8">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
