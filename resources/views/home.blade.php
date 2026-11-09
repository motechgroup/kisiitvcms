@extends('layouts.news')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- ========================================== -->
    <!-- SECTION 1: MASTER HERO (LEAD & FOCUS)      -->
    <!-- ========================================== -->
    <section class="pb-8 border-b-2 border-slate-900">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
            
            <!-- LEFT & CENTER (8 COLS): MAIN LEAD + 2 SUB-FEATURES -->
            <div class="lg:col-span-8 flex flex-col justify-between space-y-6">
                
                @if($leadStory)
                    <!-- Primary Lead Story with Split Architecture -->
                    <article class="group bg-white border border-slate-200 hover:border-[#1b52bc] transition p-4 sm:p-5 shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                            
                            <!-- Lead Image (6 cols on tablet/desktop) -->
                            <div class="md:col-span-6 relative aspect-[16/10] overflow-hidden bg-slate-900">
                                <img src="{{ $leadStory->featured_image }}" alt="{{ $leadStory->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                @if($leadStory->is_breaking)
                                    <span class="absolute top-2.5 left-2.5 bg-[#d9252a] text-white text-[10px] font-black uppercase tracking-wider px-2 py-0.5 shadow-md flex items-center space-x-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        <span>Breaking Story</span>
                                    </span>
                                @endif
                                @if($leadStory->article_type === 'video' || $leadStory->video_url)
                                    <div class="absolute bottom-2.5 right-2.5 bg-black/80 text-white p-2 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Lead Narrative Content (6 cols) -->
                            <div class="md:col-span-6 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center space-x-2 text-[11px] font-extrabold uppercase tracking-wider text-[#d9252a] mb-1.5">
                                        <span>{{ $leadStory->category->name ?? 'Special Report' }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-500 font-medium">{{ $leadStory->published_at?->diffForHumans() }}</span>
                                    </div>

                                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-950 group-hover:text-[#1b52bc] transition leading-tight font-headline">
                                        <a href="{{ route('articles.show', $leadStory->slug) }}">
                                            {{ $leadStory->title }}
                                        </a>
                                    </h1>

                                    <p class="text-xs sm:text-sm text-slate-600 mt-2 line-clamp-3 leading-relaxed">
                                        {{ $leadStory->summary }}
                                    </p>

                                    <!-- Key Takeaways / Related Bullets -->
                                    <div class="mt-3 pt-2.5 border-t border-slate-100 space-y-1 text-xs">
                                        <div class="flex items-start space-x-1.5">
                                            <span class="text-[#d9252a] font-bold text-xs">▶</span>
                                            <span class="font-semibold text-slate-800">Sh415B baseline disbursed directly to all 47 counties</span>
                                        </div>
                                        <div class="flex items-start space-x-1.5">
                                            <span class="text-[#d9252a] font-bold text-xs">▶</span>
                                            <span class="text-slate-600">Health, rural roads & water infrastructure prioritized</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="font-bold text-slate-800">By {{ $leadStory->author->name ?? 'David Ochieng' }}</span>
                                    <span>{{ $leadStory->reading_time_minutes ?? 4 }} min read</span>
                                </div>
                            </div>

                        </div>
                    </article>
                @endif

                <!-- 2 Secondary Featured Stories Below the Lead (Side-by-Side) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach($secondaryFeatured->take(2) as $article)
                        <article class="group bg-white border border-slate-200/80 hover:border-[#1b52bc] p-3.5 transition flex flex-col justify-between shadow-sm">
                            <div>
                                <div class="relative w-full aspect-[16/10] overflow-hidden bg-slate-900 mb-2.5">
                                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    <span class="absolute top-2 left-2 bg-[#0a1c3d] text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5">
                                        {{ $article->category->name ?? 'News' }}
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-2">
                                    <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                                </h3>
                                <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed">
                                    {{ $article->summary }}
                                </p>
                            </div>
                            <div class="text-[11px] text-slate-400 pt-2 mt-2 border-t border-slate-100 flex items-center justify-between">
                                <span>By {{ $article->author->name ?? 'News Desk' }}</span>
                                <span>{{ $article->published_at?->diffForHumans() }}</span>
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
                    @if(isset($sidebarAd) && $sidebarAd)
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

                <!-- Bureau Desk Alert -->
                <div class="border-l-4 border-[#d9252a] bg-red-50 p-3 text-xs text-red-950">
                    <div class="font-bold uppercase tracking-wider text-[10px] text-red-800">Newsroom Tip Line</div>
                    <p class="mt-1">Got breaking news or an investigative tip? Reach our 24/7 confidential editorial desk at <strong class="underline">newsroom@kisiitv.com</strong>.</p>
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
    <!-- SECTION 4: KENYA & NATIONAL AFFAIRS (CATEGORY GRID)      -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['kenya']) && $categoryArticles['kenya']['articles']->isNotEmpty())
        <section id="category-kenya" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Kenya &amp; National Headlines
                    </h2>
                </div>
                <a href="{{ route('categories.show', 'kenya') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    View National Wire &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['kenya']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 5: POLITICS & GOVERNANCE                         -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['politics']) && $categoryArticles['politics']['articles']->isNotEmpty())
        <section id="category-politics" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Politics &amp; National Governance
                    </h2>
                </div>
                <a href="{{ route('categories.show', 'politics') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    View Political Wire &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['politics']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 6: COUNTIES & DEVOLUTION                         -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['counties']) && $categoryArticles['counties']['articles']->isNotEmpty())
        <section id="category-counties" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Counties &amp; Devolution Affairs
                    </h2>
                    <span class="text-xs text-slate-500 hidden sm:inline">Grassroots reporting from Kisii, Nyamira, Nairobi, Mombasa &amp; Western</span>
                </div>
                <a href="{{ route('categories.show', 'counties') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    All Counties &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['counties']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 7: IN-CONTENT BILLBOARD AD (970x250)             -->
    <!-- ======================================================== -->
    <section class="my-8 py-2">
        <div class="w-full bg-slate-50 border border-slate-200 p-2 text-center max-w-5xl mx-auto">
            <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Sponsored Commercial Feature</span>
            @if(isset($contentAd) && $contentAd)
                <a href="{{ $contentAd->target_url }}" target="_blank" rel="noopener" class="block overflow-hidden group">
                    <img src="{{ $contentAd->image_path }}" alt="{{ $contentAd->title }}" class="w-full h-auto max-h-56 object-cover mx-auto">
                </a>
            @else
                <div class="w-full h-32 sm:h-44 bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white p-6 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-red-300">Media Partnership Opportunities</span>
                        <h3 class="text-lg sm:text-2xl font-bold font-headline mt-1">Connect Your Brand with Kisii TV's Expanding Audience</h3>
                        <p class="text-xs text-slate-200 mt-1 hidden sm:block">Leaderboard campaigns, native stories, and prime television broadcast sponsorships.</p>
                    </div>
                    <a href="{{ route('login') }}" class="px-5 py-2.5 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider transition shrink-0">
                        Inquire Rates
                    </a>
                </div>
            @endif
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 8: BUSINESS, MARKETS & ECONOMY                   -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['business']) && $categoryArticles['business']['articles']->isNotEmpty())
        <section id="category-business" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                    Business, Markets &amp; Economy
                </h2>
                <a href="{{ route('categories.show', 'business') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Market Desk &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['business']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 9: TECHNOLOGY & SILICON SAVANNAH                 -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['technology']) && $categoryArticles['technology']['articles']->isNotEmpty())
        <section id="category-technology" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Technology &amp; Silicon Savannah
                    </h2>
                    <span class="text-xs text-slate-500 hidden sm:inline">AI, Fintech, Mobile Broadband &amp; Green Energy</span>
                </div>
                <a href="{{ route('categories.show', 'technology') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Tech Pulse &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['technology']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 10: CRIME & INVESTIGATIVE REPORTING              -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['crime']) && $categoryArticles['crime']['articles']->isNotEmpty())
        <section id="category-crime" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#d9252a]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Crime, Courts &amp; Anti-Corruption
                    </h2>
                </div>
                <a href="{{ route('categories.show', 'crime') }}" class="text-xs font-bold uppercase tracking-wider text-[#d9252a] hover:underline">
                    Investigative Files &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['crime']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 11: EDUCATION & ACADEMIC REFORMS                 -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['education']) && $categoryArticles['education']['articles']->isNotEmpty())
        <section id="category-education" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                    Education &amp; Campus Affairs
                </h2>
                <a href="{{ route('categories.show', 'education') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Education Wire &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['education']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 12: SPORTS & ATHLETICS SUPREMACY                 -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['sports']) && $categoryArticles['sports']['articles']->isNotEmpty())
        <section id="category-sports" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Sports &amp; Athletics Supremacy
                    </h2>
                    <span class="text-xs text-slate-500 hidden sm:inline">Marathons, Harambee Stars, Shujaa Sevens &amp; Grassroots Soccer</span>
                </div>
                <a href="{{ route('categories.show', 'sports') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Sports Desk &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['sports']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 13: WORLD & DIPLOMATIC AFFAIRS                   -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['world']) && $categoryArticles['world']['articles']->isNotEmpty())
        <section id="category-world" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                    World &amp; Regional Affairs
                </h2>
                <a href="{{ route('categories.show', 'world') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Diplomatic Desk &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['world']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 14: ENTERTAINMENT, CULTURE & ARTS               -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['entertainment']) && $categoryArticles['entertainment']['articles']->isNotEmpty())
        <section id="category-entertainment" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Entertainment, Music &amp; Culture
                    </h2>
                    <span class="text-xs text-slate-500 hidden sm:inline">Cinema, Gusii Heritage, Performing Arts &amp; Celebrity</span>
                </div>
                <a href="{{ route('categories.show', 'entertainment') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Arts &amp; Culture Desk &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['entertainment']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 15: LIFESTYLE, WELLNESS & TRAVEL                 -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['lifestyle']) && $categoryArticles['lifestyle']['articles']->isNotEmpty())
        <section id="category-lifestyle" class="py-8 border-b border-slate-200">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                        Lifestyle, Wellness &amp; Travel
                    </h2>
                    <span class="text-xs text-slate-500 hidden sm:inline">Healthy Living, Safari Getaways &amp; Culinary Explorations</span>
                </div>
                <a href="{{ route('categories.show', 'lifestyle') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Lifestyle Wire &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['lifestyle']['articles'] as $article)
                    <x-article-card :article="$article" variant="standard" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- ======================================================== -->
    <!-- SECTION 16: FEATURED VIDEO BROADCAST SHOWCASE            -->
    <!-- ======================================================== -->
    @if(isset($categoryArticles['video']) && $categoryArticles['video']['articles']->isNotEmpty())
        <section id="featured-videos" class="my-10 bg-[#0a1c3d] text-white p-6 sm:p-8 border-t-4 border-[#d9252a]">
            <div class="flex items-center justify-between pb-3 mb-6 border-b border-blue-900">
                <div class="flex items-center space-x-3">
                    <span class="bg-[#d9252a] text-white p-1 rounded">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <h2 class="text-xl font-extrabold uppercase tracking-tight text-white font-headline">
                        Featured Video Reports &amp; Broadcast Bulletins
                    </h2>
                </div>
                <a href="{{ route('categories.show', 'video') }}" class="text-xs font-bold uppercase tracking-wider text-red-400 hover:text-white transition">
                    Browse All Video Broadcasts &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
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
        <section id="category-opinion" class="py-8">
            <div class="flex items-center justify-between pb-2 mb-6 border-b-2 border-[#1b52bc]">
                <h2 class="text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline">
                    Editorial Board &amp; Thought Leadership
                </h2>
                <a href="{{ route('categories.show', 'opinion') }}" class="text-xs font-bold uppercase tracking-wider text-[#1b52bc] hover:underline">
                    Read All Columnists &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryArticles['opinion']['articles'] as $article)
                    <div class="border border-slate-200 bg-slate-50 p-5 flex flex-col justify-between hover:border-[#1b52bc] transition group shadow-sm">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#d9252a] block mb-2">Columnist Viewpoint</span>
                            <h3 class="text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition font-headline leading-snug">
                                <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                            </h3>
                            <p class="text-xs text-slate-600 mt-2 line-clamp-3 leading-relaxed">
                                {{ $article->summary }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-3 pt-4 mt-4 border-t border-slate-200">
                            <div class="w-9 h-9 rounded-full bg-[#0a1c3d] text-white font-bold text-xs flex items-center justify-center shrink-0 uppercase border border-blue-400">
                                {{ substr($article->author->name ?? 'ED', 0, 2) }}
                            </div>
                            <div class="text-xs">
                                <div class="font-bold text-slate-950">{{ $article->author->name ?? 'Sarah Moraa' }}</div>
                                <div class="text-slate-500 text-[10px]">{{ $article->author->job_title ?? 'Senior Editorial Columnist' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
