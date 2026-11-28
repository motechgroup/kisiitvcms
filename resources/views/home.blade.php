@extends('layouts.news')

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

    <!-- ========================================== -->
    <!-- SECTION 1: MASTER HERO (LEAD & FOCUS)      -->
    <!-- ========================================== -->
    <section class="pb-6 sm:pb-8 border-b-2 border-slate-900">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8">

            <!-- LEFT & CENTER (8 COLS): MAIN LEAD + 2 SUB-FEATURES -->
            <div class="lg:col-span-8 flex flex-col justify-between space-y-4 sm:space-y-6">

                @if($leadStory)
                    <!-- Primary Lead Story: Full-bleed Hero Card on Mobile, Split Editorial on Desktop -->
                    <article class="group bg-white rounded-2xl border border-slate-200/90 hover:border-[#1b52bc]/60 overflow-hidden transition-all duration-300 shadow-sm hover:shadow-md">
                        <div class="grid grid-cols-1 md:grid-cols-12 items-stretch">

                            <!-- Lead Image (6 cols on tablet/desktop; aspect-auto prevents width overflow into text column) -->
                            <a href="{{ route('articles.show', $leadStory->slug) }}" class="md:col-span-6 min-w-0 relative block w-full aspect-[16/10] md:aspect-auto md:min-h-[340px] overflow-hidden bg-slate-900">
                                <img src="{{ $leadStory->featured_image }}" alt="{{ $leadStory->title }}" class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-105 transition duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent md:hidden"></div>

                                @if($leadStory->is_breaking)
                                    <span class="absolute top-3.5 left-3.5 z-10 bg-[#d9252a] text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded shadow-md flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        <span>Breaking Story</span>
                                    </span>
                                @endif
                                @if($leadStory->article_type === 'video' || $leadStory->video_url)
                                    <div class="absolute bottom-3 right-3 z-10 bg-black/80 text-white p-2 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                @endif

                                <!-- Mobile Overlay Title -->
                                <div class="absolute inset-x-0 bottom-0 z-10 p-4 md:hidden">
                                    <div class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-red-400 mb-1">
                                        <span>{{ $leadStory->category->name ?? 'Special Report' }}</span>
                                        <span class="text-slate-400">&bull;</span>
                                        <span class="text-slate-300 font-medium">{{ $leadStory->published_at?->diffForHumans() }}</span>
                                    </div>
                                    <h1 class="text-lg sm:text-xl font-extrabold tracking-tight text-white leading-snug font-headline">
                                        {{ $leadStory->title }}
                                    </h1>
                                </div>
                            </a>

                            <!-- Lead Narrative Content (6 cols) -->
                            <div class="md:col-span-6 min-w-0 p-5 sm:p-6 lg:p-7 flex flex-col justify-between bg-white">
                                <div>
                                    <div class="hidden md:flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider mb-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-50 text-[#d9252a] border border-red-100">
                                            {{ $leadStory->category->name ?? 'Special Report' }}
                                        </span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="text-slate-500 font-semibold">{{ $leadStory->published_at?->diffForHumans() }}</span>
                                    </div>

                                    <h1 class="hidden md:block text-xl lg:text-[26px] font-extrabold tracking-tight text-slate-950 group-hover:text-[#1b52bc] transition leading-[1.18] font-headline">
                                        <a href="{{ route('articles.show', $leadStory->slug) }}">
                                            {{ $leadStory->title }}
                                        </a>
                                    </h1>

                                    <p class="text-xs sm:text-sm text-slate-600 md:mt-3 line-clamp-3 leading-relaxed">
                                        {{ $leadStory->summary }}
                                    </p>
                                </div>

                                <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-bold text-slate-800 truncate">By {{ $leadStory->author->name ?? 'Kisii TV Desk' }}</span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="shrink-0">{{ $leadStory->reading_time_minutes ?? 4 }} min read</span>
                                    </div>
                                    <a href="{{ route('articles.show', $leadStory->slug) }}" class="shrink-0 font-extrabold text-[#1b52bc] group-hover:text-[#d9252a] transition uppercase tracking-wider text-[10px]">
                                        Read &rarr;
                                    </a>
                                </div>
                            </div>

                        </div>
                    </article>
                @endif

                <!-- 2 Secondary Featured Stories Below the Lead (2-Column Grid Even on Mobile) -->
                <div class="grid grid-cols-2 gap-3 sm:gap-5">
                    @foreach($secondaryFeatured->take(2) as $article)
                        <article class="group bg-white rounded-xl border border-slate-200/80 hover:border-[#1b52bc] overflow-hidden transition flex flex-col justify-between shadow-2xs">
                            <div>
                                <a href="{{ route('articles.show', $article->slug) }}" class="relative block w-full aspect-[16/10] overflow-hidden bg-slate-900">
                                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    <span class="absolute top-2 left-2 bg-[#0a1c3d]/90 text-white text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-xs">
                                        {{ $article->category->name ?? 'News' }}
                                    </span>
                                </a>
                                <div class="p-2.5 sm:p-3.5">
                                    <h3 class="text-[13px] sm:text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-3 sm:line-clamp-2">
                                        <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                                    </h3>
                                    <p class="hidden sm:block text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed">
                                        {{ $article->summary }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 px-2.5 sm:px-3.5 pb-2.5 pt-2 border-t border-slate-100 flex items-center justify-between gap-1">
                                <span class="truncate">{{ $article->published_at?->diffForHumans() }}</span>
                                <span class="hidden sm:inline truncate">By {{ $article->author->name ?? 'News Desk' }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>

            </div>

            <!-- RIGHT COLUMN (4 COLS): "TOP HEADLINES WIRE" (High Density, Aligned Height) -->
            <div class="lg:col-span-4 flex flex-col justify-between border-t lg:border-t-0 lg:border-l border-slate-200 pt-6 lg:pt-0 lg:pl-6 space-y-4">
                
                <div class="flex items-center justify-between pb-2 border-b-2 border-[#1b52bc]">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#d9252a] inline-block"></span>
                        <span>Top Headlines Wire</span>
                    </h2>
                    <span class="text-[10px] font-bold text-[#d9252a] uppercase tracking-wider animate-pulse flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#d9252a]"></span>
                        <span>Live Desk</span>
                    </span>
                </div>

                <!-- 3rd Featured Story (Visual Compact Card) -->
                @if($secondaryFeatured->count() > 2)
                    @php $thirdFeature = $secondaryFeatured->get(2); @endphp
                    <article class="group bg-slate-50 p-3 border border-slate-200 hover:border-[#1b52bc] transition">
                        <div class="relative w-full aspect-[16/9] overflow-hidden bg-slate-900 mb-2">
                            <img src="{{ $thirdFeature->featured_image }}" alt="{{ $thirdFeature->title }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            <span class="absolute top-2 left-2 bg-[#d9252a] text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5">
                                Editorial Pick
                            </span>
                        </div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#d9252a] block">
                            {{ $thirdFeature->category->name ?? 'Special' }}
                        </span>
                        <h4 class="text-sm font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-2 mt-0.5">
                            <a href="{{ route('articles.show', $thirdFeature->slug) }}">{{ $thirdFeature->title }}</a>
                        </h4>
                    </article>
                @endif

                <!-- Compact Wire Headlines with Thumbnails -->
                <div class="divide-y divide-slate-100 flex-grow flex flex-col justify-between">
                    @foreach($latestNews->take(3) as $wireItem)
                        <article class="group py-2.5 first:pt-0 last:pb-0 flex items-start space-x-3">
                            <div class="w-20 aspect-[4/3] shrink-0 overflow-hidden bg-slate-900 border border-slate-200">
                                <img src="{{ $wireItem->featured_image }}" alt="{{ $wireItem->title }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            </div>
                            <div class="flex-grow min-w-0">
                                <span class="text-[9px] font-extrabold uppercase tracking-wider text-[#1b52bc] block">
                                    {{ $wireItem->category->name ?? 'Wire' }}
                                </span>
                                <h5 class="text-xs font-bold text-slate-950 group-hover:text-[#d9252a] transition leading-snug font-headline line-clamp-2">
                                    <a href="{{ route('articles.show', $wireItem->slug) }}">{{ $wireItem->title }}</a>
                                </h5>
                                <span class="text-[10px] text-slate-400 block mt-0.5">
                                    {{ $wireItem->published_at?->diffForHumans() }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Live Breaking Newsroom Alert Strip -->
                <div class="bg-[#0a1c3d] text-white p-3 border-l-4 border-[#d9252a] text-xs shadow-sm">
                    <div class="flex items-center space-x-2">
                        <span class="bg-[#d9252a] text-white text-[9px] font-black uppercase px-1.5 py-0.5 tracking-wider">Flash</span>
                        <span class="font-extrabold text-[10px] uppercase tracking-wider text-red-300">County Desk</span>
                    </div>
                    <div class="font-bold text-slate-100 mt-1 text-xs">
                        Devolution Commission announces technical disbursement schedule for 47 counties.
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SUPPORT KISII TV EDITORIAL FUND (COMMUNITY SUPPORT BANNER) -->
    <!-- ======================================================== -->
    <section class="my-6 bg-gradient-to-r from-[#0a1c3d] via-[#102a5c] to-[#0a1c3d] text-white p-5 sm:p-6 rounded-xl border-l-4 border-emerald-500 shadow-lg">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="space-y-1.5 max-w-2xl">
                <h3 class="text-lg sm:text-xl font-black font-headline text-white leading-tight">
                    Help Kisii TV Keep In-Depth County Investigations &amp; Live News Free For All
                </h3>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    We rely on reader support to send reporters into the field, cover county barazas, and broadcast breaking news live without paywalls.
                </p>
            </div>

            <!-- Quick Donate CTA and Preset Chips -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" @click="$dispatch('open-donate-modal', { amount: 200 })"
                        class="px-2.5 py-1.5 rounded bg-slate-900/80 border border-slate-700 hover:border-emerald-400 text-xs font-bold text-slate-200 hover:text-white transition cursor-pointer">
                        KES 200
                    </button>
                    <button type="button" @click="$dispatch('open-donate-modal', { amount: 500 })"
                        class="px-2.5 py-1.5 rounded bg-slate-900/80 border border-slate-700 hover:border-emerald-400 text-xs font-bold text-slate-200 hover:text-white transition cursor-pointer">
                        KES 500
                    </button>
                    <button type="button" @click="$dispatch('open-donate-modal', { amount: 1000 })"
                        class="px-2.5 py-1.5 rounded bg-slate-900/80 border border-slate-700 hover:border-emerald-400 text-xs font-bold text-slate-200 hover:text-white transition cursor-pointer">
                        KES 1,000
                    </button>
                </div>

                <a href="{{ route('donations.index') }}" @click.prevent="$dispatch('open-donate-modal')"
                    class="px-5 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider shadow-xl transition flex items-center justify-center space-x-2 cursor-pointer whitespace-nowrap">
                    <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span>Donate to Kisii TV</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 2: 3-COLUMN WIRE (LATEST, MOST READ, SIDEBAR AD) -->
    <!-- ======================================================== -->
    <section class="py-8 border-b border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

            <!-- Col 1: Latest News Wire (5 Cols) -->
            <div class="md:col-span-5">
                <div class="flex items-center justify-between pb-2 mb-4 border-b-2 border-[#1b52bc]">
                    <h2 class="text-base font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#d9252a] inline-block"></span>
                        <span>Latest News Wire</span>
                    </h2>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Live Desk</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($latestNews as $article)
                        <x-article-card :article="$article" variant="horizontal" />
                    @endforeach
                </div>
            </div>

            <!-- Col 2: Most Read Stories (4 Cols) -->
            <div class="md:col-span-4 border-l border-r border-slate-200 px-0 md:px-6">
                <div class="flex items-center justify-between pb-2 mb-4 border-b-2 border-[#d9252a]">
                    <h2 class="text-base font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#1b52bc] inline-block"></span>
                        <span>Trending / Most Read</span>
                    </h2>
                    <span class="text-[11px] font-bold text-[#d9252a] uppercase tracking-wider">Top Reads</span>
                </div>

                <div class="space-y-4">
                    @foreach($mostRead as $index => $article)
                        <div class="flex items-start space-x-3 group">
                            <span class="text-2xl font-black text-slate-300 group-hover:text-[#d9252a] transition shrink-0 font-headline">
                                {{ sprintf('%02d', $index + 1) }}
                            </span>
                            <div class="flex-grow">
                                <span class="text-[9px] font-extrabold uppercase tracking-wider text-[#d9252a]">
                                    {{ $article->category->name ?? 'News' }}
                                </span>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline">
                                    <a href="{{ route('articles.show', $article->slug) }}">
                                        {{ $article->title }}
                                    </a>
                                </h3>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">
                                    {{ number_format($article->view_count) }} views
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Col 3: Sidebar Monetization & Live Focus (3 Cols) -->
            <div class="md:col-span-3 space-y-6">
                <!-- Ad Placement: 300x250 Medium Rectangle -->
                <div class="w-full bg-slate-100 border border-slate-200 p-2 text-center">
                    <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Sponsored Content</span>
                    @if($adsenseSidebarUnit = \App\Models\SiteSetting::renderAdsenseUnit('adsense_sidebar_code'))
                        <div class="w-full min-h-[250px] flex items-center justify-center overflow-hidden">
                            {!! $adsenseSidebarUnit !!}
                        </div>
                    @elseif(isset($sidebarAd) && $sidebarAd)
                        <a href="{{ $sidebarAd->target_url }}" target="_blank" rel="noopener" class="block overflow-hidden group">
                            <img src="{{ $sidebarAd->image_path }}" alt="{{ $sidebarAd->title }}" class="w-full h-auto object-cover group-hover:opacity-95 transition">
                        </a>
                    @else
                        <div class="w-full h-60 bg-[#0a1c3d] text-white p-4 flex flex-col justify-between text-left">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-red-400">Kisii TV Commercial</span>
                                <h4 class="text-sm font-bold mt-1">Target 500,000+ Regional Decision Makers</h4>
                            </div>
                            <a href="{{ route('login') }}" class="py-2 px-3 bg-[#d9252a] text-center text-xs font-bold uppercase tracking-wider text-white">
                                Reserve Slot
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Trending Topics Cloud -->
                <div class="bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 pb-2 mb-3 border-b border-slate-300">
                        Trending News Topics
                    </h3>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($trendingTags as $tag)
                            <span class="text-[11px] font-semibold bg-white border border-slate-300 px-2 py-1 text-slate-700 hover:bg-[#1b52bc] hover:text-white hover:border-[#1b52bc] transition cursor-pointer">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Support Kisii TV Patron Sidebar Card -->
                <div class="bg-gradient-to-br from-slate-900 to-[#0a1c3d] border-2 border-emerald-500/40 rounded-xl p-4 text-white shadow-md space-y-3">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Support Our Desk</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold font-headline text-white">Stand With Independent Journalism</h4>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            Support Kisii TV's ground reporting, investigative scoops, and community broadcasts across Kenya.
                        </p>
                    </div>
                    <a href="{{ route('donations.index') }}" @click.prevent="$dispatch('open-donate-modal')"
                        class="w-full py-2.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider text-center flex items-center justify-center space-x-1.5 shadow transition cursor-pointer">
                        <svg class="w-3.5 h-3.5 fill-current text-white" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span>Support Kisii TV Now</span>
                    </a>
                </div>

                <!-- Bureau Desk Alert -->
                @php
                    $deskEmail = \App\Models\SiteSetting::get('contact_email', 'kisiitelevision@gmail.com');
                @endphp
                <div class="border-l-4 border-[#d9252a] bg-red-50 p-3 text-xs text-red-950">
                    <div class="font-bold uppercase tracking-wider text-[10px] text-red-800">Newsroom Tip Line</div>
                    <p class="mt-1">Got breaking news or an investigative tip? Reach our 24/7 confidential editorial desk at <a href="mailto:{{ $deskEmail }}" class="font-bold underline">{{ $deskEmail }}</a>.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 3: LIVE BLOG DEVELOPING COVERAGE                  -->
    <!-- ======================================================== -->
    @if(isset($liveBlog) && $liveBlog)
        <section id="live-coverage" class="my-10 bg-[#0a1c3d] text-white border-t-4 border-[#d9252a] p-6 lg:p-8 shadow-md">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 mb-6 border-b border-blue-900 gap-4">
                <div class="flex items-center space-x-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-[#d9252a]"></span>
                    </span>
                    <span class="bg-[#d9252a] text-white text-xs font-black uppercase tracking-wider px-2 py-0.5">Developing Story</span>
                    <h2 class="text-lg sm:text-xl font-bold tracking-tight text-white font-headline">
                        {{ $liveBlog->title }}
                    </h2>
                </div>
                <div class="text-xs text-slate-400">
                    Live Coverage Started: {{ $liveBlog->started_at?->timezone('Africa/Nairobi')->format('H:i T') }}
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Cover Image & Summary (5 Cols) -->
                <div class="lg:col-span-5 space-y-3">
                    @if($liveBlog->cover_image)
                        <div class="w-full aspect-video overflow-hidden bg-slate-900 border border-slate-800">
                            <img src="{{ $liveBlog->cover_image }}" alt="{{ $liveBlog->title }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        {{ $liveBlog->summary }}
                    </p>
                </div>

                <!-- Live Stream of Updates (7 Cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-red-400 mb-2">Live Timeline Entries</div>
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2 divide-y divide-slate-800">
                        @foreach($liveBlog->updates as $update)
                            <div class="pt-3 first:pt-0">
                                <div class="flex items-center space-x-2 text-[11px] text-slate-400 mb-1">
                                    <span class="bg-red-950 text-red-300 font-mono font-bold px-1.5 py-0.5 border border-red-800">
                                        {{ $update->published_at->timezone('Africa/Nairobi')->format('H:i') }} EAT
                                    </span>
                                    @if($update->is_pinned)
                                        <span class="bg-amber-400 text-slate-950 font-black uppercase text-[9px] px-1.5 py-0.5">PINNED KEY EVENT</span>
                                    @endif
                                    <span>By {{ $update->author->name ?? 'Newsroom Desk' }}</span>
                                </div>
                                @if($update->title)
                                    <h4 class="text-sm font-bold text-white mb-1">{{ $update->title }}</h4>
                                @endif
                                <div class="text-xs text-slate-300 leading-relaxed">
                                    {{ $update->content }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- CATEGORY HERO + RESPONSIVE GRID SECTIONS                 -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['kenya']))
        <x-category-section
            id="category-kenya"
            title="Kenya & National"
            slug="kenya"
            link-label="National Wire"
            :articles="$categoryArticles['kenya']['articles']"
        />
    @endif

    @if(isset($categoryArticles['politics']))
        <x-category-section
            id="category-politics"
            title="Politics & Governance"
            slug="politics"
            link-label="Political Wire"
            :articles="$categoryArticles['politics']['articles']"
        />
    @endif

    @if(isset($categoryArticles['counties']))
        <x-category-section
            id="category-counties"
            title="Counties & Devolution"
            subtitle="Grassroots reporting from Kisii, Nyamira, Nairobi, Mombasa & Western"
            slug="counties"
            link-label="All Counties"
            :articles="$categoryArticles['counties']['articles']"
        />
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 7: IN-CONTENT BILLBOARD AD (970x250)             -->
    <!-- ======================================================== -->
    <section class="my-6 sm:my-8 py-2">
        <div class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-center max-w-5xl mx-auto">
            <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Sponsored Commercial Feature</span>
            @if($adsenseInArticleUnit = \App\Models\SiteSetting::renderAdsenseUnit('adsense_in_article_code'))
                <div class="w-full min-h-[120px] flex items-center justify-center overflow-hidden">
                    {!! $adsenseInArticleUnit !!}
                </div>
            @elseif(isset($contentAd) && $contentAd)
                <a href="{{ $contentAd->target_url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg group">
                    <img src="{{ $contentAd->image_path }}" alt="{{ $contentAd->title }}" loading="lazy" class="w-full h-auto max-h-56 object-cover mx-auto">
                </a>
            @else
                <div class="w-full rounded-lg bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-left">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-red-300">Media Partnership Opportunities</span>
                        <h3 class="text-base sm:text-2xl font-bold font-headline mt-1">Connect Your Brand with Kisii TV's Expanding Audience</h3>
                        <p class="text-xs text-slate-200 mt-1 hidden sm:block">Leaderboard campaigns, native stories, and prime television broadcast sponsorships.</p>
                    </div>
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded-md transition shrink-0">
                        Inquire Rates
                    </a>
                </div>
            @endif
        </div>
    </section>

    @if(isset($categoryArticles['business']))
        <x-category-section
            id="category-business"
            title="Business & Economy"
            slug="business"
            link-label="Market Desk"
            :articles="$categoryArticles['business']['articles']"
        />
    @endif

    @if(isset($categoryArticles['technology']))
        <x-category-section
            id="category-technology"
            title="Technology & Innovation"
            subtitle="AI, Fintech, Mobile Broadband & Green Energy"
            slug="technology"
            link-label="Tech Pulse"
            :articles="$categoryArticles['technology']['articles']"
        />
    @endif

    @if(isset($categoryArticles['crime']))
        <x-category-section
            id="category-crime"
            title="Crime & Courts"
            slug="crime"
            link-label="Investigative Files"
            accent="red"
            :articles="$categoryArticles['crime']['articles']"
        />
    @endif

    @if(isset($categoryArticles['education']))
        <x-category-section
            id="category-education"
            title="Education & Campus"
            slug="education"
            link-label="Education Wire"
            :articles="$categoryArticles['education']['articles']"
        />
    @endif

    @if(isset($categoryArticles['sports']))
        <x-category-section
            id="category-sports"
            title="Sports & Athletics"
            subtitle="Marathons, Harambee Stars, Shujaa Sevens & Grassroots Soccer"
            slug="sports"
            link-label="Sports Desk"
            :articles="$categoryArticles['sports']['articles']"
        />
    @endif

    @if(isset($categoryArticles['world']))
        <x-category-section
            id="category-world"
            title="World & Regional"
            slug="world"
            link-label="Diplomatic Desk"
            :articles="$categoryArticles['world']['articles']"
        />
    @endif

    @if(isset($categoryArticles['entertainment']))
        <x-category-section
            id="category-entertainment"
            title="Entertainment & Culture"
            subtitle="Cinema, Gusii Heritage, Performing Arts & Celebrity"
            slug="entertainment"
            link-label="Arts & Culture"
            :articles="$categoryArticles['entertainment']['articles']"
        />
    @endif

    @if(isset($categoryArticles['lifestyle']))
        <x-category-section
            id="category-lifestyle"
            title="Lifestyle & Travel"
            subtitle="Healthy Living, Safari Getaways & Culinary Explorations"
            slug="lifestyle"
            link-label="Lifestyle Wire"
            :articles="$categoryArticles['lifestyle']['articles']"
        />
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 16: FEATURED VIDEO BROADCAST SHOWCASE            -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['video']) && $categoryArticles['video']['articles']->isNotEmpty())
        <section id="featured-videos" class="my-8 bg-[#0a1c3d] text-white p-4 sm:p-8 rounded-xl border-t-4 border-[#d9252a]">
            <div class="flex items-center justify-between gap-2 pb-3 mb-5 border-b border-blue-900">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="bg-[#d9252a] text-white p-1 rounded shrink-0">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <h2 class="text-base sm:text-xl font-extrabold uppercase tracking-tight text-white font-headline truncate">
                        Video Broadcasts
                    </h2>
                </div>
                <a href="{{ route('categories.show', 'video') }}" class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-red-400 hover:text-white transition shrink-0">
                    All Videos &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                @foreach($categoryArticles['video']['articles'] as $article)
                    <x-article-card :article="$article" variant="video" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 17: OPINION & EDITORIAL COMMENTARY               -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['opinion']) && $categoryArticles['opinion']['articles']->isNotEmpty())
        <section id="category-opinion" class="py-6 sm:py-8">
            <div class="flex items-center justify-between gap-2 pb-2 mb-5 border-b-2 border-[#1b52bc]">
                <h2 class="text-base sm:text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                    Opinion &amp; Editorial
                </h2>
                <a href="{{ route('categories.show', 'opinion') }}" class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline shrink-0">
                    All Columnists &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                @foreach($categoryArticles['opinion']['articles'] as $article)
                    <div class="border border-slate-200 bg-slate-50 rounded-xl p-3.5 sm:p-5 flex flex-col justify-between hover:border-[#1b52bc] transition group shadow-2xs">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#d9252a] block mb-1.5">Columnist</span>
                            <h3 class="text-xs sm:text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition font-headline leading-snug line-clamp-3">
                                <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                            </h3>
                            <p class="hidden sm:block text-xs text-slate-600 mt-2 line-clamp-3 leading-relaxed">
                                {{ $article->summary }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 pt-3 mt-3 border-t border-slate-200">
                            <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-full bg-[#0a1c3d] text-white font-bold text-[10px] sm:text-xs flex items-center justify-center shrink-0 uppercase border border-blue-400">
                                {{ substr($article->author->name ?? 'ED', 0, 2) }}
                            </div>
                            <div class="text-[10px] sm:text-xs min-w-0">
                                <div class="font-bold text-slate-950 truncate">{{ $article->author->name ?? 'Editorial Desk' }}</div>
                                <div class="text-slate-500 text-[9px] sm:text-[10px] truncate">{{ $article->author->job_title ?? 'Columnist' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
