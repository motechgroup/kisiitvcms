@props([
    'article',
    'variant' => 'standard',
    'showImage' => true,
    'showSummary' => true,
])

@php
    $categoryName = $article->category->name ?? 'News';
    $categorySlug = $article->category->slug ?? 'news';
    $authorName = $article->author->name ?? 'News Desk';
    $timeAgo = $article->published_at ? $article->published_at->diffForHumans() : 'Just now';
    $imageUrl = $article->featured_image ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=800&q=80';
    $articleUrl = route('articles.show', $article->slug);
    $categoryUrl = route('categories.show', $categorySlug);
@endphp

@if($variant === 'lead')
    <!-- LEAD STORY: Prominent high-impact card -->
    <article class="group relative flex flex-col bg-white">
        @if($showImage)
            <div class="relative w-full aspect-[16/9] overflow-hidden bg-slate-900 mb-4">
                <a href="{{ $articleUrl }}">
                    <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                </a>
                @if($article->is_breaking)
                    <span class="absolute top-3 left-3 bg-[#d9252a] text-white text-xs font-black uppercase tracking-wider px-2.5 py-1 shadow-md">
                        Breaking Story
                    </span>
                @endif
                @if($article->article_type === 'video' || $article->video_url)
                    <div class="absolute bottom-3 right-3 bg-black/80 text-white p-2.5 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                @endif
            </div>
        @endif

        <div class="space-y-2">
            <div class="flex items-center space-x-2 text-xs font-extrabold uppercase tracking-wider text-[#d9252a]">
                <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
                <span class="text-slate-400">•</span>
                <span class="text-slate-500 font-medium">{{ $timeAgo }}</span>
            </div>

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-slate-950 group-hover:text-[#1b52bc] transition leading-tight font-headline">
                <a href="{{ $articleUrl }}" class="hover:underline">
                    {{ $article->title }}
                </a>
            </h1>

            @if($showSummary && $article->summary)
                <p class="text-sm sm:text-base text-slate-700 leading-relaxed pt-1">
                    {{ $article->summary }}
                </p>
            @endif

            <div class="flex items-center space-x-3 text-xs text-slate-500 pt-2 border-t border-slate-100">
                <span class="font-bold text-slate-900">By {{ $authorName }}</span>
                <span>•</span>
                <span>{{ $article->reading_time_minutes ?? 3 }} min read</span>
            </div>
        </div>
    </article>

@elseif($variant === 'featured')
    <!-- SECONDARY FEATURED: Medium prominent card -->
    <article class="group flex flex-col bg-white">
        @if($showImage)
            <div class="relative w-full aspect-[16/10] overflow-hidden bg-slate-900 mb-3">
                <a href="{{ $articleUrl }}">
                    <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                </a>
                @if($article->is_breaking)
                    <span class="absolute top-2 left-2 bg-[#d9252a] text-white text-[10px] font-black uppercase tracking-wider px-2 py-0.5">
                        Breaking
                    </span>
                @endif
            </div>
        @endif

        <div class="space-y-1.5 flex-grow flex flex-col justify-between">
            <div>
                <div class="text-[11px] font-extrabold uppercase tracking-wider text-[#d9252a] mb-1">
                    <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
                </div>
                <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline">
                    <a href="{{ $articleUrl }}">
                        {{ $article->title }}
                    </a>
                </h2>
                @if($showSummary && $article->summary)
                    <p class="text-xs text-slate-600 line-clamp-2 mt-1.5 leading-relaxed">
                        {{ $article->summary }}
                    </p>
                @endif
            </div>

            <div class="text-[11px] text-slate-500 pt-2 mt-2 border-t border-slate-100 flex items-center justify-between">
                <span>By {{ $authorName }}</span>
                <span>{{ $timeAgo }}</span>
            </div>
        </div>
    </article>

@elseif($variant === 'horizontal')
    <!-- HORIZONTAL CARD: Thumbnail on left, content on right -->
    <article class="group flex items-start space-x-3 py-3 border-b border-slate-100 last:border-b-0">
        @if($showImage)
            <a href="{{ $articleUrl }}" class="relative w-24 sm:w-28 aspect-[4/3] shrink-0 overflow-hidden bg-slate-900">
                <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition">
            </a>
        @endif

        <div class="flex-grow min-w-0">
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-[#d9252a] mb-0.5">
                <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
            </div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-2">
                <a href="{{ $articleUrl }}">
                    {{ $article->title }}
                </a>
            </h3>
            <div class="text-[10px] text-slate-500 mt-1 flex items-center space-x-1.5">
                <span>{{ $timeAgo }}</span>
                <span>•</span>
                <span>{{ $article->reading_time_minutes ?? 2 }}m</span>
            </div>
        </div>
    </article>

@elseif($variant === 'text-only')
    <!-- TEXT ONLY / HEADLINE WIRE: High density listing -->
    <article class="group py-2.5 border-b border-slate-100 last:border-b-0">
        <div class="text-[10px] font-extrabold uppercase tracking-wider text-[#d9252a] mb-0.5">
            <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
        </div>
        <h3 class="text-sm font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline">
            <a href="{{ $articleUrl }}">
                {{ $article->title }}
            </a>
        </h3>
        <div class="text-[10px] text-slate-400 mt-1">
            {{ $timeAgo }}
        </div>
    </article>

@elseif($variant === 'video')
    <!-- VIDEO CARD: Video badge & play overlay -->
    <article class="group flex flex-col bg-slate-900 text-white p-3">
        <a href="{{ $articleUrl }}" class="relative w-full aspect-video overflow-hidden bg-black mb-3 block">
            <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition">
            <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                <div class="w-10 h-10 bg-[#d9252a] rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                    <svg class="w-5 h-5 fill-current text-white ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
            <span class="absolute bottom-2 left-2 bg-black/80 text-[10px] font-bold px-1.5 py-0.5 tracking-wider uppercase">
                Video Broadcast
            </span>
        </a>
        <h3 class="text-sm font-bold text-white group-hover:text-red-400 transition font-headline leading-snug line-clamp-2">
            <a href="{{ $articleUrl }}">
                {{ $article->title }}
            </a>
        </h3>
        <div class="text-[10px] text-slate-400 mt-2 flex items-center justify-between">
            <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
            <span>{{ $timeAgo }}</span>
        </div>
    </article>

@else
    <!-- STANDARD CARD: Vertical grid card -->
    <article class="group flex flex-col bg-white">
        @if($showImage)
            <a href="{{ $articleUrl }}" class="relative w-full aspect-[16/10] overflow-hidden bg-slate-900 mb-2.5 block">
                <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition">
            </a>
        @endif

        <div class="space-y-1">
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-[#d9252a]">
                <a href="{{ $categoryUrl }}" class="hover:underline">{{ $categoryName }}</a>
            </div>
            <h3 class="text-sm sm:text-base font-bold text-slate-950 group-hover:text-[#1b52bc] transition leading-snug font-headline line-clamp-2">
                <a href="{{ $articleUrl }}">
                    {{ $article->title }}
                </a>
            </h3>
            @if($showSummary && $article->summary)
                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                    {{ $article->summary }}
                </p>
            @endif
            <div class="text-[10px] text-slate-500 pt-1">
                {{ $timeAgo }} • By {{ $authorName }}
            </div>
        </div>
    </article>
@endif
