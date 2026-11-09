@extends('layouts.news')

@php
    $title = $category->name . ' News, Analysis & Reports';
    $metaDescription = $category->description ?: ('Explore the latest ' . $category->name . ' news headlines, investigative coverage, and updates on Kisii TV.');
    $canonicalUrl = route('categories.show', $category->slug);
    $metaKeywords = $category->name . ', ' . $category->name . ' news, Kisii TV, Amandegere Y’omogusii, Kenya ' . $category->name;
@endphp

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
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
            "name": "{{ addslashes($category->name) }}",
            "item": "{{ route('categories.show', $category->slug) }}"
        }
    ]
}
</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Category Header Masthead -->
    <div class="bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white p-6 sm:p-8 mb-8 border-b-4 border-[#d9252a] shadow">
        <div class="flex items-center space-x-2 text-[11px] font-bold uppercase tracking-widest text-red-300 mb-2">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span>/</span>
            <span>Section Wire</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-extrabold font-headline uppercase tracking-tight text-white">
            {{ $category->name }}
        </h1>
        @if($category->description)
            <p class="text-xs sm:text-sm text-slate-200 mt-2 max-w-3xl leading-relaxed">
                {{ $category->description }}
            </p>
        @endif

        <!-- Subcategories Filter Pills -->
        @if($subcategories->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-4 mt-4 border-t border-blue-900/60">
                <span class="text-[10px] font-black uppercase text-blue-300 self-center tracking-wider mr-1">Topics:</span>
                @foreach($subcategories as $sub)
                    <a href="{{ route('categories.show', $sub->slug) }}" 
                       class="text-xs font-semibold bg-white/10 hover:bg-[#d9252a] text-white px-3 py-1 rounded-full transition">
                        {{ $sub->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Main Content & Sidebar Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- Articles Grid (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            @if($articles->isNotEmpty())
                <!-- Lead Story for Category -->
                @php $leadCatStory = $articles->first(); @endphp
                <div class="border-b-2 border-slate-900 pb-8">
                    <x-article-card :article="$leadCatStory" variant="lead" />
                </div>

                <!-- Remaining Articles Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($articles->skip(1) as $art)
                        <x-article-card :article="$art" variant="standard" />
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="pt-6 border-t border-slate-200">
                    {{ $articles->links() }}
                </div>
            @else
                <div class="p-12 text-center bg-slate-50 border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-700 font-headline">No Published Articles Yet</h3>
                    <p class="text-xs text-slate-500 mt-1">Check back soon as our newsroom files stories for {{ $category->name }}.</p>
                    <a href="{{ route('home') }}" class="inline-block mt-4 px-4 py-2 bg-[#1b52bc] text-white text-xs font-bold uppercase tracking-wider rounded">
                        Return to Homepage
                    </a>
                </div>
            @endif
        </div>

        <!-- Sidebar Rail (4 cols) -->
        <aside class="lg:col-span-4 space-y-8">
            <!-- Sidebar Ad -->
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
                            <h4 class="text-sm font-bold mt-1 font-headline">Target 500,000+ Regional Readers</h4>
                        </div>
                        <a href="{{ route('login') }}" class="py-2 px-3 bg-[#d9252a] text-center text-xs font-bold uppercase tracking-wider text-white">
                            Reserve Slot
                        </a>
                    </div>
                @endif
            </div>

            <!-- Most Read Trending List -->
            <div class="bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-4 border-b-2 border-[#d9252a]">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-headline flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 bg-[#1b52bc] inline-block"></span>
                        <span>Trending Across Network</span>
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

    </div>

</div>
@endsection
