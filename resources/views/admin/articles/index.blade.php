@extends('layouts.admin')

@section('title', 'Editorial Article Management - Kisii TV')
@section('page_title', 'Editorial Articles & Broadcast Content')

@section('content')
<div class="space-y-6" x-data="{
    selected: [],
    selectAll: false,
    toggleAll() {
        if (this.selectAll) {
            this.selected = Array.from(document.querySelectorAll('.article-checkbox')).map(el => el.value);
        } else {
            this.selected = [];
        }
    }
}">

    <!-- Header Actions & Metric Ribbon -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">Editorial Publishing &amp; Stories</h2>
            <p class="text-xs text-slate-500 mt-0.5">Newsroom Editorial Content: Author, review, schedule, and mass-publish articles across Kisii TV news sections.</p>
        </div>
        <div class="flex items-center space-x-2.5">
            <a href="{{ route('home') }}" target="_blank" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>Live Portal</span>
            </a>
            <a href="{{ route('admin.articles.create') }}" class="px-4 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded shadow transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Write New Story</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Tiles -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Total Catalog</span>
            <span class="text-xl font-black text-slate-900 font-headline mt-1 block">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 block">Published Live</span>
            <span class="text-xl font-black text-emerald-700 font-headline mt-1 block">{{ number_format($stats['published']) }}</span>
        </div>
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-600 block">In Review Queue</span>
            <span class="text-xl font-black text-amber-700 font-headline mt-1 block">{{ number_format($stats['in_review']) }}</span>
        </div>
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block">Drafts</span>
            <span class="text-xl font-black text-slate-700 font-headline mt-1 block">{{ number_format($stats['draft']) }}</span>
        </div>
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-red-600 block">Breaking Alerts</span>
            <span class="text-xl font-black text-[#d9252a] font-headline mt-1 block">{{ number_format($stats['breaking']) }}</span>
        </div>
        <div class="bg-white p-3.5 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 block">Featured Leads</span>
            <span class="text-xl font-black text-[#1b52bc] font-headline mt-1 block">{{ number_format($stats['featured']) }}</span>
        </div>
    </div>

    <!-- Filters, Search & Sort Toolbar -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search headline, summary or keywords..."
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div>
                <select name="category_id" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    <option value="">All Statuses</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published Live</option>
                    <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>In Review</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                </select>
            </div>

            <div>
                <select name="flag" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    <option value="">All Editorial Flags</option>
                    <option value="breaking" {{ request('flag') == 'breaking' ? 'selected' : '' }}>⚡ Breaking Only</option>
                    <option value="featured" {{ request('flag') == 'featured' ? 'selected' : '' }}>★ Featured Only</option>
                    <option value="editors_pick" {{ request('flag') == 'editors_pick' ? 'selected' : '' }}>✦ Editor's Pick</option>
                </select>
            </div>

            <div>
                <select name="sort" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Sort: Newest First</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
                    <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>Sort: Most Views</option>
                    <option value="updated" {{ request('sort') == 'updated' ? 'selected' : '' }}>Sort: Recently Updated</option>
                    <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Sort: Headline A-Z</option>
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-6 flex items-center justify-between pt-2 border-t border-slate-100">
                <div class="flex items-center space-x-2">
                    <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['search', 'category_id', 'status', 'flag', 'author_id', 'sort']))
                        <a href="{{ route('admin.articles.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded">
                            Reset Filters
                        </a>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500">
                    Showing <strong>{{ $articles->firstItem() ?? 0 }}</strong> to <strong>{{ $articles->lastItem() ?? 0 }}</strong> of <strong>{{ $articles->total() }}</strong> stories
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Action Floating Bar -->
    <form method="POST" action="{{ route('admin.articles.bulk') }}" id="bulkActionForm">
        @csrf
        <div x-show="selected.length > 0" x-transition class="bg-[#0a1c3d] text-white p-3 rounded-lg shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span class="text-xs font-bold">
                    <span x-text="selected.length"></span> article(s) selected
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Hidden inputs for selected article IDs -->
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="selected_articles[]" :value="id">
                </template>

                <select name="action" required class="bg-slate-800 text-white text-xs border border-slate-700 rounded px-3 py-1.5 focus:outline-none">
                    <option value="">Choose Bulk Action...</option>
                    <option value="publish">Publish Live</option>
                    <option value="draft">Move to Drafts</option>
                    <option value="in_review">Move to Review</option>
                    <option value="feature">Mark as Featured</option>
                    <option value="unfeature">Unmark Featured</option>
                    <option value="breaking">Flag as Breaking Alert</option>
                    <option value="unbreaking">Remove Breaking Alert</option>
                    <option value="delete">Delete Selected</option>
                </select>

                <button type="submit" onclick="return confirm('Execute this bulk action on selected articles?')"
                    class="px-4 py-1.5 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                    Execute
                </button>
                <button type="button" @click="selected = []; selectAll = false" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Articles Table -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3 w-8 text-center">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll" class="rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                            </th>
                            <th class="py-3 px-3">Story Headline &amp; Details</th>
                            <th class="py-3 px-3">Category</th>
                            <th class="py-3 px-3">Author</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 text-center">Flags</th>
                            <th class="py-3 px-3 text-center">Views</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($articles as $article)
                            <tr class="hover:bg-slate-50/80 transition" :class="{ 'bg-blue-50/50': selected.includes('{{ $article->id }}') }">
                                <td class="py-3 px-3 text-center">
                                    <input type="checkbox" value="{{ $article->id }}" x-model="selected" class="article-checkbox rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                                </td>
                                <td class="py-3 px-3 max-w-md">
                                    <div class="flex items-start space-x-3">
                                        <img src="{{ $article->featured_image }}" alt="" class="w-16 h-11 object-cover rounded bg-slate-100 shrink-0 border border-slate-200">
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 mb-0.5">
                                                <span class="text-[9px] uppercase font-extrabold px-1.5 py-0.2 rounded
                                                    {{ $article->article_type === 'video' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }}">
                                                    {{ ucfirst($article->article_type ?? 'standard') }}
                                                </span>
                                                <span class="text-[10px] text-slate-400">•</span>
                                                <span class="text-[10px] text-slate-500 font-medium">
                                                    {{ $article->published_at ? $article->published_at->timezone('Africa/Nairobi')->format('M d, Y') : 'Not Published' }}
                                                </span>
                                            </div>
                                            <a href="{{ route('admin.articles.edit', $article->slug) }}" class="font-bold text-slate-900 hover:text-[#1b52bc] transition line-clamp-2 leading-snug">
                                                {{ $article->title }}
                                            </a>
                                            <span class="text-[10px] text-slate-400 font-mono block truncate mt-0.5">
                                                /articles/{{ $article->slug }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-800 block">{{ $article->category->name ?? 'Unassigned' }}</span>
                                    @if($article->subcategory)
                                        <span class="text-[10px] text-slate-500 block">&rdquor; {{ $article->subcategory->name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-semibold text-slate-800 block">{{ $article->author->name ?? 'News Desk' }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $article->author->job_title ?? 'Journalist' }}</span>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.articles.toggle-status', $article->slug) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Click to toggle Status"
                                            class="inline-block text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full cursor-pointer transition
                                            {{ $article->status === 'published' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : ($article->status === 'in_review' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-slate-200 text-slate-700 hover:bg-slate-300') }}">
                                            ● {{ ucfirst($article->status) }}
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1">
                                        <!-- Breaking toggle -->
                                        <form method="POST" action="{{ route('admin.articles.toggle-breaking', $article->slug) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Toggle Breaking Alert"
                                                class="p-1 rounded text-xs font-bold transition {{ $article->is_breaking ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-slate-300 hover:text-slate-500' }}">
                                                ⚡
                                            </button>
                                        </form>

                                        <!-- Featured toggle -->
                                        <form method="POST" action="{{ route('admin.articles.toggle-featured', $article->slug) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Toggle Featured Lead"
                                                class="p-1 rounded text-xs font-bold transition {{ $article->is_featured ? 'text-amber-500 bg-amber-50 hover:bg-amber-100' : 'text-slate-300 hover:text-slate-500' }}">
                                                ★
                                            </button>
                                        </form>

                                        <!-- Editor's Pick toggle -->
                                        <form method="POST" action="{{ route('admin.articles.toggle-editors-pick', $article->slug) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Toggle Editor's Pick"
                                                class="p-1 rounded text-xs font-bold transition {{ $article->is_editors_pick ? 'text-blue-600 bg-blue-50 hover:bg-blue-100' : 'text-slate-300 hover:text-slate-500' }}">
                                                ✦
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap text-slate-600 font-mono">
                                    {{ number_format($article->view_count) }}
                                    <span class="text-[10px] text-slate-400 block font-sans">{{ $article->reading_time_minutes ?? 3 }}m read</span>
                                </td>
                                <td class="py-3 px-3 text-right whitespace-nowrap space-x-1">
                                    <!-- Public preview -->
                                    <a href="{{ route('articles.show', $article->slug) }}" target="_blank" title="View Public Page"
                                        class="p-1.5 text-slate-500 hover:text-[#1b52bc] rounded hover:bg-slate-100 inline-block">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>

                                    <!-- Clone article -->
                                    <form method="POST" action="{{ route('admin.articles.clone', $article->slug) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="Duplicate as Draft" class="p-1.5 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 inline-block cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                        </button>
                                    </form>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.articles.edit', $article->slug) }}" title="Edit Story"
                                        class="p-1.5 text-[#1b52bc] hover:text-blue-900 rounded hover:bg-blue-50 inline-block">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('admin.articles.destroy', $article->slug) }}" class="inline" onsubmit="return confirm('Delete this story? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Story" class="p-1.5 text-red-500 hover:text-red-700 rounded hover:bg-red-50 inline-block">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    <p class="font-bold text-sm">No articles match your criteria.</p>
                                    <p class="text-xs text-slate-400 mt-1">Try resetting your search query or filters.</p>
                                    <a href="{{ route('admin.articles.create') }}" class="inline-block mt-3 px-4 py-2 bg-[#1b52bc] text-white text-xs font-bold rounded">
                                        + Compose Story
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination footer -->
            <div class="p-4 border-t border-slate-200">
                {{ $articles->links() }}
            </div>
        </div>
    </form>
</div>
@endsection
