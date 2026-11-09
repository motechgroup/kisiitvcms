@extends('layouts.admin')

@section('title', 'Super Administrator Command Center')
@section('page_title', 'Super Admin Command Center & Newsroom Operations')

@section('content')
<div class="space-y-8" x-data="{
    activeTab: '{{ $inReviewCount > 0 ? 'review' : 'published' }}',
    showQuickStoryModal: false,
    showBreakingModal: false
}">

    <!-- ======================================================== -->
    <!-- 1. SUPER ADMIN EXECUTIVE IDENTITY & QUICK ACTIONS BANNER -->
    <!-- ======================================================== -->
    <div class="bg-gradient-to-r from-[#071329] via-[#0a1c3d] to-[#1b52bc] text-white p-6 sm:p-8 rounded-lg shadow-lg border border-slate-800 relative overflow-hidden">
        <!-- Background Ambient Accent -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#d9252a]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="bg-[#d9252a] text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded shadow-sm">
                        Super Administrator
                    </span>
                    <span class="bg-blue-900/80 text-blue-200 border border-blue-700/50 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                        Executive Newsroom Sovereign
                    </span>
                    <span class="text-xs text-slate-300 font-mono hidden sm:inline">
                        {{ $systemInfo['server_time'] }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight font-headline text-white">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    {{ auth()->user()->job_title ?? 'Chief Technology & Systems Officer' }} &bull; Overseeing full broadcast channels, editorial review pipeline, navigation taxonomy, and live breaking alerts.
                </p>
            </div>

            <!-- Quick Action Launcher Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <button type="button" @click="showBreakingModal = true" class="px-4 py-2.5 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded shadow transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                    <span>+ Breaking News Alert</span>
                </button>

                <button type="button" @click="showQuickStoryModal = true" class="px-4 py-2.5 bg-[#1b52bc] hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded shadow transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Quick Story Dispatch</span>
                </button>

                <a href="{{ route('admin.categories.index') }}" class="px-3.5 py-2.5 bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold uppercase tracking-wider rounded border border-slate-700 transition flex items-center space-x-1.5">
                    <span>Menu &amp; Categories</span>
                    <span class="text-[10px] bg-blue-600 text-white px-1.5 py-0.2 rounded font-mono">{{ $menuCategoriesCount }}</span>
                </a>
            </div>
        </div>

        <!-- Telemetry Status Ribbon -->
        <div class="mt-6 pt-4 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs text-slate-300">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>System: <strong>Operational</strong></span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <span>Database: <strong>{{ strtoupper($systemInfo['database']) }} Live</strong></span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                <span>Environment: <strong>{{ ucfirst($systemInfo['environment']) }}</strong></span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                <span>Live Breaking: <strong>{{ $activeBreakingCount }} Broadcasting</strong></span>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 2. HIGH-IMPACT EXECUTIVE METRIC TILES                    -->
    <!-- ======================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <!-- 1. Total Published -->
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm hover:border-[#1b52bc] transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Published Stories</span>
                <span class="w-7 h-7 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">✓</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900 font-headline">{{ number_format($publishedCount) }}</span>
                @if($todayPublishedCount > 0)
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">+{{ $todayPublishedCount }} today</span>
                @endif
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Live across 13 news channels</span>
        </div>

        <!-- 2. Readership Impressions -->
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm hover:border-[#1b52bc] transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Total Readership</span>
                <span class="w-7 h-7 rounded bg-red-50 text-[#d9252a] flex items-center justify-center font-bold text-xs">▲</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-[#d9252a] font-headline">{{ number_format($totalViews) }}</span>
                <span class="text-[10px] text-slate-400">views</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Reader engagement telemetry</span>
        </div>

        <!-- 3. Needs Review Queue -->
        <div class="bg-white p-4 border {{ $inReviewCount > 0 ? 'border-amber-400 bg-amber-50/20' : 'border-slate-200' }} rounded shadow-sm hover:border-amber-500 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Review Queue</span>
                <span class="w-7 h-7 rounded bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">⏳</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-amber-900 font-headline">{{ number_format($inReviewCount) }}</span>
                @if($inReviewCount > 0)
                    <span class="text-[10px] font-black text-white bg-amber-600 px-1.5 py-0.5 rounded uppercase">Pending</span>
                @else
                    <span class="text-[10px] text-slate-400">Clear</span>
                @endif
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Stories awaiting publication</span>
        </div>

        <!-- 4. Drafts & In-Progress -->
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm hover:border-[#1b52bc] transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Drafts Desk</span>
                <span class="w-7 h-7 rounded bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">✎</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900 font-headline">{{ number_format($draftCount) }}</span>
                <span class="text-[10px] text-slate-400">drafts</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Reporters preparing stories</span>
        </div>

        <!-- 5. Navigation Menu Items -->
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm hover:border-[#1b52bc] transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Menu Categories</span>
                <a href="{{ route('admin.categories.index') }}" class="w-7 h-7 rounded bg-blue-50 text-[#1b52bc] flex items-center justify-center font-bold text-xs hover:bg-[#1b52bc] hover:text-white transition">☰</a>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-[#1b52bc] font-headline">{{ number_format($menuCategoriesCount) }}</span>
                <span class="text-[10px] text-slate-500">/ {{ $totalCategories }} total</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Live on top navigation bar</span>
        </div>

        <!-- 6. Active Staff & Desks -->
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm hover:border-[#1b52bc] transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Newsroom Staff</span>
                <span class="w-7 h-7 rounded bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">👥</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900 font-headline">{{ number_format($totalStaffCount) }}</span>
                <span class="text-[10px] text-emerald-600 font-bold">Active</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Across editorial bureaus</span>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 3. BREAKING NEWS BROADCAST CONTROL DESK                   -->
    <!-- ======================================================== -->
    <div id="breaking-desk" class="bg-white border-2 border-[#d9252a]/80 rounded-lg shadow-sm overflow-hidden">
        <div class="bg-[#d9252a] text-white p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <span class="w-3 h-3 rounded-full bg-white animate-pulse"></span>
                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider font-headline">
                        Breaking News Live Broadcast Ticker Controller
                    </h2>
                    <p class="text-[11px] text-red-100">Dispatches real-time flashing ticker alerts directly to the public homepage header.</p>
                </div>
            </div>
            <button type="button" @click="showBreakingModal = true" class="px-3.5 py-1.5 bg-white text-[#d9252a] hover:bg-red-50 text-xs font-black uppercase tracking-wider rounded transition shrink-0 shadow">
                + New Breaking Alert
            </button>
        </div>

        <div class="p-4 sm:p-5">
            @if($breakingNewsItems->isEmpty())
                <div class="text-center py-6 text-slate-400 text-xs">
                    No breaking news alerts currently registered. Click "+ New Breaking Alert" to dispatch an urgent broadcast.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($breakingNewsItems as $item)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 px-2 rounded transition">
                            <div class="flex items-start space-x-3 min-w-0">
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded shrink-0 {{ $item->is_active ? 'bg-red-100 text-[#d9252a]' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $item->is_active ? 'LIVE TICKER' : 'PAUSED' }}
                                </span>
                                <div class="min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">
                                        {{ $item->alert_text }}
                                    </h4>
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex flex-wrap items-center gap-2">
                                        <span>Priority: <strong>{{ $item->priority }}</strong></span>
                                        <span>•</span>
                                        <span>Dispatched: {{ $item->created_at->diffForHumans() }}</span>
                                        @if($item->url)
                                            <span>•</span>
                                            <a href="{{ $item->url }}" target="_blank" class="text-[#1b52bc] hover:underline truncate max-w-xs">{{ $item->url }}</a>
                                        @elseif($item->article)
                                            <span>•</span>
                                            <a href="{{ route('articles.show', $item->article->slug) }}" target="_blank" class="text-[#1b52bc] hover:underline truncate">Story: {{ $item->article->title }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                <form method="POST" action="{{ route('admin.breaking-news.toggle', $item->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded transition {{ $item->is_active ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }}">
                                        {{ $item->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.breaking-news.destroy', $item->id) }}" onsubmit="return confirm('Remove this breaking news ticker alert?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs text-red-600 hover:text-red-900 font-bold transition">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 4. EDITORIAL WORKFLOW & PUBLISHING STATION               -->
    <!-- ======================================================== -->
    <div id="review-queue" class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <!-- Station Header with Multi-Tab Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold uppercase tracking-tight text-slate-900 font-headline">
                    Editorial Publishing Station &amp; Workflow Queues
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Manage articles from raw draft through editorial review and live national broadcast.</p>
            </div>

            <!-- Tabs -->
            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-md text-xs font-bold">
                <button type="button" @click="activeTab = 'review'" :class="activeTab === 'review' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded transition flex items-center space-x-1.5">
                    <span>Review Queue</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] {{ $inReviewCount > 0 ? 'bg-[#d9252a] text-white' : 'bg-slate-200 text-slate-600' }}">
                        {{ $inReviewCount }}
                    </span>
                </button>

                <button type="button" @click="activeTab = 'published'" :class="activeTab === 'published' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded transition flex items-center space-x-1.5">
                    <span>Live Published</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-emerald-100 text-emerald-800">
                        {{ $publishedCount }}
                    </span>
                </button>

                <button type="button" @click="activeTab = 'drafts'" :class="activeTab === 'drafts' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded transition flex items-center space-x-1.5">
                    <span>Drafts</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-200 text-slate-600">
                        {{ $draftCount }}
                    </span>
                </button>

                <button type="button" @click="activeTab = 'top'" :class="activeTab === 'top' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded transition flex items-center space-x-1.5">
                    <span>Top Readership</span>
                    <span class="text-amber-500">★</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: REVIEW QUEUE -->
        <div x-show="activeTab === 'review'" class="divide-y divide-slate-100">
            @if($reviewQueue->isEmpty())
                <div class="p-8 text-center">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-xl font-bold mb-2">✓</div>
                    <h4 class="text-sm font-bold text-slate-900">Review Queue is Clear</h4>
                    <p class="text-xs text-slate-500 mt-1">All submitted articles have been reviewed and published. Newsroom reporters will file new drafts soon.</p>
                </div>
            @else
                @foreach($reviewQueue as $article)
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                        <div class="flex items-start space-x-3 min-w-0">
                            <div class="w-16 h-12 rounded bg-slate-200 overflow-hidden shrink-0">
                                <img src="{{ $article->featured_image }}" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-wider mb-1">
                                    <span class="text-[#1b52bc]">{{ $article->category->name ?? 'News' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-amber-600 bg-amber-50 px-1.5 py-0.2 rounded">Needs Super Admin Approval</span>
                                </div>
                                <h4 class="text-sm font-bold text-slate-950 truncate">{{ $article->title }}</h4>
                                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">{{ $article->summary }}</p>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    By <strong>{{ $article->author->name ?? 'Staff' }}</strong> &bull; Filed {{ $article->updated_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 shrink-0">
                            <form method="POST" action="{{ route('admin.articles.publish', $article) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded transition shadow-sm">
                                    Approve &amp; Publish Live
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- TAB 2: LIVE PUBLISHED ARTICLES -->
        <div x-show="activeTab === 'published'" class="divide-y divide-slate-100">
            @foreach($recentPublished as $article)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                    <div class="flex items-start space-x-3 min-w-0">
                        <div class="w-16 h-12 rounded bg-slate-200 overflow-hidden shrink-0">
                            <img src="{{ $article->featured_image }}" alt="" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-wider mb-1">
                                <span class="text-[#1b52bc]">{{ $article->category->name ?? 'News' }}</span>
                                <span class="text-slate-300">•</span>
                                <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded">Live Broadcast</span>
                                @if($article->is_featured)
                                    <span class="bg-[#d9252a] text-white px-1.5 py-0.2 rounded">Featured Lead</span>
                                @endif
                            </div>
                            <h4 class="text-sm font-bold text-slate-950 truncate">
                                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="hover:text-[#1b52bc]">
                                    {{ $article->title }}
                                </a>
                            </h4>
                            <div class="text-[10px] text-slate-400 mt-1">
                                By <strong>{{ $article->author->name ?? 'Staff' }}</strong> &bull; Published {{ $article->published_at?->diffForHumans() }} &bull; {{ $article->reading_time_minutes }} min read
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 shrink-0">
                        <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded">
                            {{ number_format($article->view_count) }} views
                        </span>
                        <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="text-xs text-[#1b52bc] hover:underline font-bold">
                            View Story &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- TAB 3: DRAFTS -->
        <div x-show="activeTab === 'drafts'" class="divide-y divide-slate-100">
            @if($recentDrafts->isEmpty())
                <div class="p-8 text-center text-slate-400 text-xs">
                    No active drafts saved. Click "+ Quick Story Dispatch" to start drafting a new piece.
                </div>
            @else
                @foreach($recentDrafts as $article)
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-slate-100 text-slate-600 rounded">
                                {{ $article->category->name ?? 'General' }}
                            </span>
                            <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $article->title }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $article->summary }}</p>
                            <span class="text-[10px] text-slate-400 mt-1 block">Author: {{ $article->author->name ?? 'Staff' }} &bull; Last updated {{ $article->updated_at->diffForHumans() }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.articles.publish', $article) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded transition">
                                Publish Direct
                            </button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- TAB 4: TOP READERSHIP -->
        <div x-show="activeTab === 'top'" class="divide-y divide-slate-100">
            @foreach($topArticles as $index => $article)
                <div class="p-4 sm:p-5 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center space-x-4 min-w-0">
                        <span class="text-2xl font-black text-slate-300 font-headline w-8 shrink-0">
                            {{ sprintf('%02d', $index + 1) }}
                        </span>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold uppercase text-[#d9252a] block">
                                {{ $article->category->name ?? 'News' }}
                            </span>
                            <h4 class="text-sm font-bold text-slate-950 truncate">
                                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="hover:text-[#1b52bc]">
                                    {{ $article->title }}
                                </a>
                            </h4>
                            <span class="text-[10px] text-slate-400">By {{ $article->author->name ?? 'Editorial Desk' }} &bull; {{ $article->published_at?->toFormattedDateString() }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-sm font-mono font-black text-[#d9252a] block">
                            {{ number_format($article->view_count) }}
                        </span>
                        <span class="text-[10px] text-slate-400">verified views</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 5. CHANNEL / CATEGORY PERFORMANCE TELEMETRY MATRIX       -->
    <!-- ======================================================== -->
    <div id="channel-matrix" class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 font-headline">
                    Broadcast Channel &amp; News Category Coverage
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Distribution of news stories, pageview telemetry, and public navigation menu status.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="px-3 py-1.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition shrink-0">
                Configure Menu Ordering &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Channel Name</th>
                        <th class="py-3 px-4">Slug / Route</th>
                        <th class="py-3 px-4 text-center">Story Volume</th>
                        <th class="py-3 px-4 text-center">Total Readership</th>
                        <th class="py-3 px-4 text-center">Public Nav Bar</th>
                        <th class="py-3 px-4 text-right">Channel Link</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $cat)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-bold text-slate-900 flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $cat->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                <span>{{ $cat->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                /category/{{ $cat->slug }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">
                                {{ $cat->articles_count }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-[#d9252a]">
                                {{ number_format((int) $cat->articles_sum_view_count) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($cat->is_menu)
                                    <span class="bg-blue-50 text-[#1b52bc] border border-blue-200 text-[10px] font-bold px-2 py-0.5 rounded">
                                        Top Menu (Order: {{ $cat->sort_order }})
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px]">Archived Only</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('categories.show', $cat->slug) }}" target="_blank" class="text-[#1b52bc] font-bold hover:underline">
                                    View Live Wire &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 6. LOWER DUAL-COLUMN: STAFF DIRECTORY & COMMERCIAL OPS   -->
    <!-- ======================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left 7 Cols: Newsroom Staff & Roles -->
        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 font-headline">
                        Newsroom Staff &amp; Editorial Bureau
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $totalStaffCount }} active journalists &amp; editors registered.</p>
                </div>
                <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded">Role-Based Access</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($staffUsers as $staff)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-[#0a1c3d] text-white font-bold flex items-center justify-center shrink-0 uppercase text-xs">
                                {{ substr($staff->name, 0, 2) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-950 truncate">{{ $staff->name }}</h4>
                                <span class="text-[10px] text-slate-500 block truncate">{{ $staff->job_title ?? 'Staff Journalist' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $staff->email }}</span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            @foreach($staff->roles as $role)
                                <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded {{ $role->slug === 'super-admin' ? 'bg-red-100 text-[#d9252a]' : 'bg-blue-100 text-[#1b52bc]' }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right 5 Cols: Monetization, Audience & System Platform -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Commercial Operations Card -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 font-headline">
                        Monetization &amp; Audience Desks
                    </h3>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Active</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-50 p-3 rounded border border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Active Ad Banners</span>
                        <span class="text-xl font-black text-slate-900 font-headline mt-1 block">{{ $totalAdsCount }} Slots</span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded border border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Newsletter Subscribers</span>
                        <span class="text-xl font-black text-[#1b52bc] font-headline mt-1 block">{{ number_format($subscribersCount) }}</span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded border border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Total Comments</span>
                        <span class="text-xl font-black text-slate-900 font-headline mt-1 block">{{ number_format($totalCommentsCount) }}</span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded border border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Pending Moderation</span>
                        <span class="text-xl font-black text-amber-600 font-headline mt-1 block">{{ number_format($commentsPendingCount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Platform Architecture & Server Telemetry -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 font-headline">
                        Platform Infrastructure
                    </h3>
                    <span class="text-[10px] font-mono text-slate-400">v{{ $systemInfo['laravel_version'] }}</span>
                </div>

                <dl class="mt-3 divide-y divide-slate-100 text-xs">
                    <div class="py-2 flex items-center justify-between">
                        <dt class="text-slate-500">Framework</dt>
                        <dd class="font-bold text-slate-900">Laravel {{ $systemInfo['laravel_version'] }}</dd>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <dt class="text-slate-500">PHP Runtime</dt>
                        <dd class="font-mono text-slate-700">{{ $systemInfo['php_version'] }}</dd>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <dt class="text-slate-500">Database Engine</dt>
                        <dd class="font-bold text-slate-900">{{ strtoupper($systemInfo['database']) }}</dd>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <dt class="text-slate-500">Newsroom Timezone</dt>
                        <dd class="text-slate-700">{{ $systemInfo['timezone'] }}</dd>
                    </div>
                </dl>
            </div>

        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: FAST BREAKING NEWS ALERT DISPATCH               -->
    <!-- ======================================================== -->
    <div x-show="showBreakingModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-transition>
        <div class="bg-white max-w-lg w-full rounded-lg shadow-2xl border border-slate-200 overflow-hidden" @click.away="showBreakingModal = false">
            <div class="bg-[#d9252a] text-white p-4 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-white animate-pulse"></span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider font-headline">
                        Dispatch Breaking News Alert
                    </h3>
                </div>
                <button type="button" @click="showBreakingModal = false" class="text-white hover:text-red-200 text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.breaking-news.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alert Ticker Headline <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="alert_text" required placeholder="e.g. NATIONAL ASSEMBLY APPROVES CRUCIAL DEVOLUTION REVENUE ALLOCATION"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] font-headline font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Target Story URL (Optional)
                    </label>
                    <input type="url" name="url" placeholder="http://127.0.0.1:8001/articles/your-slug"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Urgency Priority (1 - 10)
                        </label>
                        <input type="number" name="priority" value="8" min="1" max="10"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a]">
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-[#d9252a] focus:ring-[#d9252a]">
                            <span class="ml-2 text-xs font-bold text-slate-800">Activate on Ticker Immediately</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                    <button type="button" @click="showBreakingModal = false" class="px-4 py-2 border border-slate-300 text-xs font-bold uppercase text-slate-600 rounded hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-black uppercase tracking-wider rounded shadow transition">
                        Broadcast Live Alert
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: QUICK EDITORIAL STORY DISPATCH                  -->
    <!-- ======================================================== -->
    <div x-show="showQuickStoryModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-transition>
        <div class="bg-white max-w-xl w-full rounded-lg shadow-2xl border border-slate-200 overflow-hidden" @click.away="showQuickStoryModal = false">
            <div class="bg-[#1b52bc] text-white p-4 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-white"></span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider font-headline">
                        Quick Story Dispatch
                    </h3>
                </div>
                <button type="button" @click="showQuickStoryModal = false" class="text-white hover:text-blue-200 text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.articles.quick-create') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Article Headline <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="Enter compelling editorial headline..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc] font-headline font-bold">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Category Desk <span class="text-red-600">*</span>
                        </label>
                        <select name="category_id" required class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Publishing Status <span class="text-red-600">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                            <option value="published">Publish Immediately (Live)</option>
                            <option value="in_review">Submit for Review</option>
                            <option value="draft">Save as Working Draft</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Lead Summary &amp; Teaser <span class="text-red-600">*</span>
                    </label>
                    <textarea name="summary" rows="3" required placeholder="Summarize the core reporting and key facts for readers..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Featured Image URL (Optional)
                    </label>
                    <input type="url" name="featured_image" placeholder="https://images.unsplash.com/photo-..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>

                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                        <span class="ml-2 text-xs font-bold text-slate-800">Pin as Featured Story</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                    <button type="button" @click="showQuickStoryModal = false" class="px-4 py-2 border border-slate-300 text-xs font-bold uppercase text-slate-600 rounded hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded shadow transition">
                        Dispatch Article
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
