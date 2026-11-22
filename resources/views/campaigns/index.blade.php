@extends('layouts.news')

@section('title', 'Donation Campaigns & Initiatives — Support Kisii TV')

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-10 sm:py-14 border-b-4 border-[#d9252a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-12">
            <div class="inline-flex items-center space-x-2 bg-purple-500/10 border border-purple-500/30 text-purple-400 text-xs font-black uppercase tracking-widest px-3.5 py-1 rounded-full mb-3 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                <span>Active Newsroom Campaigns</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black font-headline tracking-tight text-white leading-tight">
                Support Our <span class="text-[#d9252a]">Special Projects</span> &amp; Causes
            </h1>
            <p class="mt-4 text-sm sm:text-base text-slate-300 leading-relaxed max-w-2xl mx-auto">
                Explore our active grassroots initiatives, from funding undercover investigative series to equipping digital broadcast vans across Kenya.
            </p>
        </div>

        @if($campaigns->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($campaigns as $camp)
                    <div class="bg-slate-900 border border-slate-800 hover:border-purple-500/50 rounded-2xl overflow-hidden shadow-xl flex flex-col justify-between transition group">
                        <div>
                            @if($camp->cover_image)
                                <div class="relative aspect-[16/9] overflow-hidden bg-slate-950">
                                    <img src="{{ $camp->cover_image }}" alt="{{ $camp->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    @if($camp->is_featured)
                                        <span class="absolute top-2.5 left-2.5 bg-purple-600 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded shadow">
                                            Featured
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="p-5 space-y-3">
                                <h3 class="text-base sm:text-lg font-bold font-headline text-white group-hover:text-purple-300 transition leading-snug line-clamp-2">
                                    <a href="{{ route('campaigns.show', $camp->slug) }}">
                                        {{ $camp->title }}
                                    </a>
                                </h3>

                                <p class="text-xs text-slate-300 line-clamp-3 leading-relaxed">
                                    {{ $camp->summary ?: Str::limit($camp->description, 120) }}
                                </p>

                                <!-- Progress Bar -->
                                <div class="pt-2">
                                    <div class="flex items-center justify-between text-xs mb-1.5 font-bold">
                                        <span class="text-emerald-400 font-mono">
                                            {{ $camp->currency }} {{ number_format($camp->total_raised ?? 0) }}
                                        </span>
                                        <span class="text-slate-400 text-[11px]">
                                            Goal: {{ $camp->currency }} {{ number_format((float) $camp->target_amount) }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-800">
                                        <div class="bg-gradient-to-r from-emerald-500 to-purple-500 h-2.5 rounded-full transition-all duration-500"
                                            style="width: {{ $camp->progressPercentage() }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                                        <span>{{ $camp->progressPercentage() }}% funded</span>
                                        <span>{{ $camp->total_donors ?? 0 }} Supporters</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 pt-0">
                            <a href="{{ route('campaigns.show', $camp->slug) }}"
                                class="w-full py-2.5 px-4 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs uppercase tracking-wider text-center flex items-center justify-center space-x-2 transition shadow">
                                <span>Support This Campaign</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $campaigns->links() }}
            </div>
        @else
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-10 text-center space-y-3">
                <span class="inline-block px-3 py-1 rounded bg-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider">No Active Campaigns</span>
                <h2 class="text-xl font-bold font-headline text-white">No Special Campaigns Running Right Now</h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto">
                    You can still support our ongoing independent newsroom reporting via our general donation fund.
                </p>
                <div class="pt-2">
                    <a href="{{ route('donations.index') }}" class="inline-block px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                        Visit General Donation Fund
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
