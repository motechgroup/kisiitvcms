@props([
    'id' => null,
    'title',
    'subtitle' => null,
    'slug',
    'linkLabel' => 'View All',
    'articles',
    'accent' => 'blue',
])

@php
    $accentBorder = $accent === 'red' ? 'border-[#d9252a]' : 'border-[#1b52bc]';
    $accentText = $accent === 'red' ? 'text-[#d9252a]' : 'text-[#1b52bc]';
    $accentDot = $accent === 'red' ? 'bg-[#d9252a]' : 'bg-[#1b52bc]';
    $heroArticle = $articles->first();
    $gridArticles = $articles->skip(1);
@endphp

@if($articles->isNotEmpty())
    <section @if($id) id="{{ $id }}" @endif class="py-6 sm:py-8 border-b border-slate-200">
        <!-- Section Header -->
        <div class="flex items-center justify-between gap-2 pb-2 mb-4 sm:mb-5 border-b-2 {{ $accentBorder }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-2 h-5 {{ $accentDot }} rounded-xs shrink-0"></span>
                <h2 class="text-base sm:text-xl font-extrabold uppercase tracking-tight text-slate-950 font-headline truncate">
                    {{ $title }}
                </h2>
                @if($subtitle)
                    <span class="text-xs text-slate-500 hidden lg:inline truncate">{{ $subtitle }}</span>
                @endif
            </div>
            <a href="{{ route('categories.show', $slug) }}"
                class="text-[11px] sm:text-xs font-bold uppercase tracking-wider {{ $accentText }} hover:underline shrink-0 whitespace-nowrap">
                {{ $linkLabel }} &rarr;
            </a>
        </div>

        <!-- Category Hero + Responsive Grid -->
        <div class="space-y-4 sm:space-y-5">
            @if($heroArticle)
                <x-article-card :article="$heroArticle" variant="category-hero" />
            @endif

            @if($gridArticles->isNotEmpty())
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">
                    @foreach($gridArticles as $article)
                        <x-article-card :article="$article" variant="standard" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
