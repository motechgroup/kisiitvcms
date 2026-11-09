@extends('layouts.news')

@php
    $title = $article->title;
    $metaDescription = $article->summary ?: Str::limit(strip_tags($article->content), 160);
    $canonicalUrl = route('articles.show', $article->slug);
    $ogType = 'article';
    $ogTitle = $article->title;
    $ogDescription = $article->summary ?: Str::limit(strip_tags($article->content), 160);
    $ogImage = $article->featured_image ?: asset('images/kisii-tv-logo.png');
    $authorName = $article->author->name ?? 'Kisii TV News Desk';
@endphp

@section('extra_og_tags')
    <meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
    <meta property="article:section" content="{{ $article->category->name ?? 'News' }}">
    <meta property="article:author" content="{{ $article->author->name ?? 'Kisii TV' }}">
@endsection

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@graph": [
        {
            "@type": "NewsArticle",
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "{{ route('articles.show', $article->slug) }}"
            },
            "headline": "{{ addslashes($article->title) }}",
            "description": "{{ addslashes($article->summary ?: Str::limit(strip_tags($article->content), 160)) }}",
            "image": [
                "{{ $article->featured_image ?: asset('images/kisii-tv-logo.png') }}"
            ],
            "datePublished": "{{ $article->published_at?->toIso8601String() }}",
            "dateModified": "{{ $article->updated_at->toIso8601String() }}",
            "author": {
                "@type": "Person",
                "name": "{{ addslashes($article->author->name ?? 'Kisii TV News Desk') }}",
                "jobTitle": "{{ addslashes($article->author->job_title ?? 'Senior Journalist') }}"
            },
            "publisher": {
                "@type": "NewsMediaOrganization",
                "name": "Kisii TV",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ asset('images/kisii-tv-logo.png') }}"
                }
            },
            "articleSection": "{{ addslashes($article->category->name ?? 'General') }}"
        },
        {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "{{ route('home') }}"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "{{ addslashes($article->category->name ?? 'News') }}",
                    "item": "{{ route('categories.show', $article->category->slug) }}"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "{{ addslashes($article->title) }}",
                    "item": "{{ route('articles.show', $article->slug) }}"
                }
            ]
        }
    ]
}
</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-4 pb-2 border-b border-slate-100">
        <a href="{{ route('home') }}" class="hover:text-[#1b52bc] transition">Home</a>
        <span>/</span>
        <a href="{{ route('categories.show', $article->category->slug) }}" class="font-bold text-[#1b52bc] hover:underline uppercase">
            {{ $article->category->name }}
        </a>
        <span>/</span>
        <span class="text-slate-400 truncate max-w-md">{{ $article->title }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- ========================================== -->
        <!-- MAIN EDITORIAL CONTENT (8 COLS)            -->
        <!-- ========================================== -->
        <article class="lg:col-span-8 space-y-6">

            <!-- Category & Timestamp -->
            <div class="flex items-center space-x-2.5 text-xs">
                <a href="{{ route('categories.show', $article->category->slug) }}" 
                   class="bg-[#d9252a] text-white font-black uppercase text-[11px] px-2.5 py-0.5 tracking-wider">
                    {{ $article->category->name }}
                </a>
                @if($article->is_breaking)
                    <span class="bg-red-100 text-[#d9252a] font-bold text-[10px] uppercase px-2 py-0.5 border border-red-200">
                        Breaking
                    </span>
                @endif
                <span class="text-slate-400">•</span>
                <span class="text-slate-500 font-medium">
                    Published {{ $article->published_at?->timezone('Africa/Nairobi')->format('M d, Y • h:i A T') }}
                </span>
            </div>

            <!-- Headline -->
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-slate-950 font-headline leading-tight">
                {{ $article->title }}
            </h1>

            <!-- Subtitle / Deck -->
            @if($article->subtitle)
                <p class="text-base sm:text-lg font-semibold text-slate-700 leading-snug">
                    {{ $article->subtitle }}
                </p>
            @endif

            <!-- Author Byline & Social Share Bar -->
            <div class="py-3 border-y border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-[#0a1c3d] text-white font-bold flex items-center justify-center shrink-0 uppercase border border-blue-400">
                        {{ substr($article->author->name ?? 'KD', 0, 2) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm">
                            By {{ $article->author->name ?? 'News Desk' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            {{ $article->author->job_title ?? 'Senior News Correspondent' }} • {{ $article->reading_time_minutes ?? 3 }} min read
                        </div>
                    </div>
                </div>

                <!-- Share Tools -->
                <div class="flex items-center space-x-1.5 text-slate-500" x-data="{ copied: false }">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Share:</span>
                    
                    <!-- WhatsApp -->
                    <a href="https://api.whatsapp.com/send?text={{ urlencode($article->title . ' - ' . request()->fullUrl()) }}" 
                       target="_blank" rel="noopener" title="Share on WhatsApp"
                       class="w-8 h-8 rounded bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </a>

                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" 
                       target="_blank" rel="noopener" title="Share on Facebook"
                       class="w-8 h-8 rounded bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>

                    <!-- X / Twitter -->
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($article->title) }}&url={{ urlencode(request()->fullUrl()) }}&via=kisiitv" 
                       target="_blank" rel="noopener" title="Share on X"
                       class="w-8 h-8 rounded bg-slate-100 hover:bg-slate-900 hover:text-white flex items-center justify-center transition">
                        <span class="font-bold text-xs">𝕏</span>
                    </a>

                    <!-- Telegram -->
                    <a href="https://t.me/share/url?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($article->title) }}" 
                       target="_blank" rel="noopener" title="Share on Telegram"
                       class="w-8 h-8 rounded bg-sky-50 text-sky-600 hover:bg-sky-500 hover:text-white flex items-center justify-center transition">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.538-.196 1.006.128.832.922z"/></svg>
                    </a>

                    <!-- Copy Link -->
                    <button type="button" @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-2.5 h-8 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center space-x-1 text-xs font-semibold transition cursor-pointer">
                        <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
                    </button>
                </div>
            </div>

            <!-- Featured Image or Video Player -->
            @if($article->featured_image)
                <div class="space-y-2">
                    <div class="relative w-full aspect-[16/9] overflow-hidden bg-slate-900 shadow-sm">
                        <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                    </div>
                    @if($article->image_caption || $article->image_credit)
                        <div class="flex items-center justify-between text-[11px] text-slate-500 px-1 italic">
                            <span>{{ $article->image_caption }}</span>
                            @if($article->image_credit)
                                <span class="font-semibold text-slate-400 ml-2 not-italic">Photo: {{ $article->image_credit }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Article Summary Callout (Lead In) -->
            @if($article->summary)
                <div class="p-4 bg-slate-50 border-l-4 border-[#1b52bc] text-slate-800 text-sm font-semibold leading-relaxed">
                    {{ $article->summary }}
                </div>
            @endif

            <!-- Full Article Body -->
            <div class="editorial-body text-base text-slate-800 leading-relaxed space-y-4 pt-2">
                {!! $article->content !!}
            </div>

            <!-- Article Tags -->
            @if($article->tags->isNotEmpty())
                <div class="pt-6 border-t border-slate-200">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-2">Related Topics:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($article->tags as $tag)
                            <span class="text-xs font-semibold bg-slate-100 border border-slate-200 px-2.5 py-1 text-slate-700 hover:bg-[#1b52bc] hover:text-white transition cursor-pointer">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Author Bio Box -->
            <div class="p-5 bg-slate-50 border border-slate-200 flex items-start space-x-4 mt-8">
                <div class="w-12 h-12 rounded-full bg-[#1b52bc] text-white font-bold flex items-center justify-center shrink-0 uppercase text-sm border-2 border-white shadow">
                    {{ substr($article->author->name ?? 'ED', 0, 2) }}
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm font-headline">About {{ $article->author->name ?? 'Staff Correspondent' }}</h4>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        {{ $article->author->bio ?? 'Senior journalist reporting on political economy, grassroots devolution, and institutional accountability for Kisii TV.' }}
                    </p>
                </div>
            </div>

            <!-- Related Articles (4-column grid) -->
            @if($relatedArticles->isNotEmpty())
                <div class="pt-8 border-t-2 border-slate-900 space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 bg-[#d9252a] inline-block"></span>
                            <span>More From {{ $article->category->name }}</span>
                        </h3>
                        <a href="{{ route('categories.show', $article->category->slug) }}" class="text-xs font-bold text-[#1b52bc] hover:underline uppercase">
                            View All &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($relatedArticles as $rel)
                            <x-article-card :article="$rel" variant="standard" />
                        @endforeach
                    </div>
                </div>
            @endif

        </article>

        <!-- ========================================== -->
        <!-- SIDEBAR RAIL (4 COLS)                      -->
        <!-- ========================================== -->
        <aside class="lg:col-span-4 space-y-8">

            <!-- Sidebar Ad (300x250) -->
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
                            <h4 class="text-sm font-bold mt-1 font-headline">Reach 500,000+ Regional Decision Makers</h4>
                        </div>
                        <a href="{{ route('login') }}" class="py-2 px-3 bg-[#d9252a] text-center text-xs font-bold uppercase tracking-wider text-white">
                            Reserve Slot
                        </a>
                    </div>
                @endif
            </div>

            <!-- Trending / Most Read -->
            <div class="bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-4 border-b-2 border-[#d9252a]">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#1b52bc] inline-block"></span>
                        <span>Trending News</span>
                    </h3>
                    <span class="text-[10px] font-bold text-[#d9252a] uppercase tracking-wider">Top Reads</span>
                </div>

                <div class="space-y-4">
                    @foreach($mostRead as $index => $item)
                        <div class="flex items-start space-x-3 group">
                            <span class="text-2xl font-black text-slate-300 group-hover:text-[#d9252a] transition shrink-0 font-headline">
                                {{ sprintf('%02d', $index + 1) }}
                            </span>
                            <div class="flex-grow min-w-0">
                                <a href="{{ route('categories.show', $item->category->slug) }}" class="text-[9px] font-extrabold uppercase tracking-wider text-[#d9252a] hover:underline">
                                    {{ $item->category->name }}
                                </a>
                                <h4 class="text-xs font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-2">
                                    <a href="{{ route('articles.show', $item->slug) }}">
                                        {{ $item->title }}
                                    </a>
                                </h4>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">
                                    {{ number_format($item->view_count) }} readers
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Latest News Wire -->
            <div class="bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-4 border-b-2 border-[#1b52bc]">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#d9252a] inline-block"></span>
                        <span>Latest Wire</span>
                    </h3>
                    <span class="text-[10px] font-bold text-[#1b52bc] uppercase tracking-wider">Live Desk</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($latestWire as $wire)
                        <div class="py-2.5 first:pt-0 last:pb-0 group">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">
                                {{ $wire->published_at?->diffForHumans() }}
                            </span>
                            <h4 class="text-xs font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline">
                                <a href="{{ route('articles.show', $wire->slug) }}">
                                    {{ $wire->title }}
                                </a>
                            </h4>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Newsroom Tip Line Callout -->
            <div class="border-l-4 border-[#d9252a] bg-red-50 p-4 text-xs text-red-950">
                <div class="font-bold uppercase tracking-wider text-[10px] text-red-800">Newsroom Tip Line</div>
                <p class="mt-1 leading-relaxed">
                    Got breaking news or an investigative tip? Reach our 24/7 confidential editorial desk at <strong class="underline">newsroom@kisiitv.com</strong>.
                </p>
            </div>

        </aside>

    </div>

</div>
@endsection
