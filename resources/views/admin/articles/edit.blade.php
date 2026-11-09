@extends('layouts.admin')

@section('title', 'Edit Story: ' . $article->title . ' - Kisii TV')
@section('page_title', 'Edit Editorial Article')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    title: '{{ addslashes(old('title', $article->title)) }}',
    content: `{{ addslashes(old('content', $article->content)) }}`,
    imageMode: 'upload',
    imageUrl: '{{ old('featured_image', $article->featured_image) }}',
    imagePreview: '{{ $article->featured_image }}',
    categoryId: '{{ old('category_id', $article->category_id) }}',
    subcategoryId: '{{ old('subcategory_id', $article->subcategory_id) }}',
    categoriesData: {{ Js::from($categories->mapWithKeys(fn($c) => [$c->id => $c->children->map(fn($sub) => ['id' => $sub->id, 'name' => $sub->name])])) }},
    articleType: '{{ old('article_type', $article->article_type ?? 'standard') }}',
    
    get wordCount() {
        if (!this.content) return 0;
        return this.content.trim().replace(/<[^>]*>?/gm, '').split(/\s+/).filter(w => w.length > 0).length;
    },
    get readingTime() {
        return Math.max(1, Math.ceil(this.wordCount / 200));
    },
    get availableSubcategories() {
        return this.categoriesData[this.categoryId] || [];
    },
    insertTag(before, after = '') {
        const textarea = this.$refs.contentField;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selectedText = text.substring(start, end);
        const replacement = before + (selectedText || 'text') + after;
        textarea.value = text.substring(0, start) + replacement + text.substring(end);
        this.content = textarea.value;
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + (selectedText ? selectedText.length : 4));
    },
    previewUpload(event) {
        const file = event.target.files[0];
        if (file) {
            this.imagePreview = URL.createObjectURL(file);
        }
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.articles.index') }}" class="font-bold hover:text-slate-900">&larr; Back to Articles</a>
                <span>/</span>
                <span class="truncate max-w-xs">{{ $article->title }}</span>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">Edit Editorial Story</h2>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-[#1b52bc]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>View Live</span>
            </a>
            <form method="POST" action="{{ route('admin.articles.clone', $article->slug) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition flex items-center space-x-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    <span>Clone Draft</span>
                </button>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-600 text-red-800 text-xs rounded shadow-sm">
            <div class="font-bold mb-1">Please correct the following errors:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.articles.update', $article->slug) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- MAIN EDITORIAL PANE (8 COLS) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Headline, Deck & Article Type -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                                Article Headline <span class="text-red-600">*</span>
                            </label>
                            <span class="text-[10px] font-mono" :class="title.length > 70 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                <span x-text="title.length"></span>/70 chars (Google SEO optimal)
                            </span>
                        </div>
                        <input type="text" name="title" x-model="title" required placeholder="Enter compelling, authoritative headline..."
                            class="w-full text-base sm:text-lg font-bold font-headline p-3 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Subtitle / Deck (Secondary Headline)
                        </label>
                        <input type="text" name="subtitle" value="{{ old('subtitle', $article->subtitle) }}" placeholder="Secondary headline providing context..."
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Story Format / Type
                            </label>
                            <select name="article_type" x-model="articleType" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                                <option value="standard">Standard News Story</option>
                                <option value="video">Video Broadcast / Live Clip</option>
                                <option value="opinion">Opinion &amp; Column</option>
                                <option value="analysis">Deep Dive &amp; Investigation</option>
                                <option value="gallery">Photojournalism Gallery</option>
                            </select>
                        </div>

                        <!-- Video URL (conditional) -->
                        <div x-show="articleType === 'video'" x-transition>
                            <label class="block text-xs font-bold uppercase tracking-wider text-purple-700 mb-1">
                                YouTube / Video Embed URL
                            </label>
                            <input type="url" name="video_url" value="{{ old('video_url', $article->video_url) }}" placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full text-xs p-2.5 border border-purple-300 rounded focus:border-purple-600">
                        </div>
                    </div>
                </div>

                <!-- Executive Summary / Lead -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                        Executive Summary / Wire Lead <span class="text-red-600">*</span>
                    </label>
                    <textarea name="summary" rows="3" required placeholder="Lead paragraph highlighting key facts..."
                        class="w-full text-xs p-3 border border-slate-300 rounded focus:border-[#1b52bc] leading-relaxed">{{ old('summary', $article->summary) }}</textarea>
                </div>

                <!-- Full Story Narrative with Formatting Toolbar -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                            Story Narrative &amp; Reporting Content <span class="text-red-600">*</span>
                        </label>
                        <div class="flex items-center space-x-3 text-[11px] text-slate-500 font-mono">
                            <span>Words: <strong class="text-slate-900" x-text="wordCount"></strong></span>
                            <span>•</span>
                            <span>Reading: <strong class="text-slate-900" x-text="readingTime + ' min'"></strong></span>
                        </div>
                    </div>

                    <!-- Quick Editorial Formatting Toolbar -->
                    <div class="flex flex-wrap items-center gap-1 p-2 bg-slate-50 border border-slate-200 rounded-t text-xs">
                        <button type="button" @click="insertTag('<strong>', '</strong>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 font-bold" title="Bold">B</button>
                        <button type="button" @click="insertTag('<em>', '</em>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 italic font-serif" title="Italic">I</button>
                        <button type="button" @click="insertTag('<h3>', '</h3>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 font-bold" title="Subheading">H3</button>
                        <button type="button" @click="insertTag('<h4>', '</h4>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 font-bold" title="Minor Heading">H4</button>
                        <button type="button" @click="insertTag('<blockquote>', '</blockquote>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 italic" title="Quote Block">&ldquo;Quote&rdquo;</button>
                        <button type="button" @click="insertTag('<ul>\n  <li>', '</li>\n</ul>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200" title="Bullet List">• List</button>
                        <button type="button" @click="insertTag('<p>', '</p>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200" title="Paragraph">&para; Para</button>
                        <button type="button" @click="insertTag('<a href=\'https://...\' target=\'_blank\'>', '</a>')" class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-200 text-[#1b52bc]" title="Hyperlink">🔗 Link</button>
                    </div>

                    <textarea name="content" x-ref="contentField" x-model="content" rows="14" required
                        class="w-full text-xs font-mono p-3 border border-t-0 border-slate-300 rounded-b focus:border-[#1b52bc] leading-relaxed">{{ old('content', $article->content) }}</textarea>
                </div>

                <!-- Featured Image Upload & Attribution -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                            Featured Story Image / Visual
                        </label>
                        <div class="flex items-center space-x-2 text-xs">
                            <button type="button" @click="imageMode = 'upload'" :class="imageMode === 'upload' ? 'bg-[#1b52bc] text-white font-bold' : 'bg-slate-100 text-slate-600'" class="px-2.5 py-1 rounded transition text-[11px]">
                                Upload New Photo
                            </button>
                            <button type="button" @click="imageMode = 'url'" :class="imageMode === 'url' ? 'bg-[#1b52bc] text-white font-bold' : 'bg-slate-100 text-slate-600'" class="px-2.5 py-1 rounded transition text-[11px]">
                                Change URL
                            </button>
                        </div>
                    </div>

                    <!-- File upload mode -->
                    <div x-show="imageMode === 'upload'" class="space-y-3">
                        <input type="file" name="featured_image_file" accept="image/*" @change="previewUpload"
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#1b52bc] hover:file:bg-blue-100 cursor-pointer">
                        <span class="text-[10px] text-slate-400 block">Accepted formats: JPG, PNG, WebP (Max 5MB). Stored directly in public uploads.</span>
                    </div>

                    <!-- URL input mode -->
                    <div x-show="imageMode === 'url'" class="space-y-3">
                        <input type="url" name="featured_image" x-model="imageUrl" placeholder="https://..."
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    </div>

                    <!-- Live Image Preview -->
                    <div x-show="imagePreview || imageUrl" class="relative w-full aspect-[16/9] bg-slate-900 rounded overflow-hidden shadow-inner">
                        <img :src="imagePreview || imageUrl" alt="Preview" class="w-full h-full object-cover">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Image Caption
                            </label>
                            <input type="text" name="image_caption" value="{{ old('image_caption', $article->image_caption) }}" placeholder="Caption..."
                                class="w-full text-xs p-2 border border-slate-300 rounded focus:border-[#1b52bc]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Photo Credit / Bureau
                            </label>
                            <input type="text" name="image_credit" value="{{ old('image_credit', $article->image_credit) }}" placeholder="Credit..."
                                class="w-full text-xs p-2 border border-slate-300 rounded focus:border-[#1b52bc]">
                        </div>
                    </div>
                </div>

                <!-- Per-Article Search Engine Optimization (SEO) -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4" x-data="{ seoOpen: false }">
                    <button type="button" @click="seoOpen = !seoOpen" class="w-full flex items-center justify-between text-left text-xs font-bold uppercase tracking-wider text-slate-800">
                        <span class="flex items-center space-x-1.5">
                            <span class="text-blue-600">✦</span>
                            <span>Search Engine Optimization (SEO &amp; Social Graph Overrides)</span>
                        </span>
                        <span x-text="seoOpen ? '▲ Collapse' : '▼ Expand'"></span>
                    </button>

                    <div x-show="seoOpen" x-transition class="space-y-4 pt-3 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Custom Meta Title (Google SERP)
                            </label>
                            <input type="text" name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" placeholder="Leave blank to use headline..."
                                class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Custom Meta Description
                            </label>
                            <textarea name="meta_description" rows="2" placeholder="Leave blank to use story summary..."
                                class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">{{ old('meta_description', $article->meta_description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Canonical URL Override
                            </label>
                            <input type="url" name="canonical_url" value="{{ old('canonical_url', $article->canonical_url) }}" placeholder="Leave blank for automatic canonical..."
                                class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                        </div>
                    </div>
                </div>

            </div>

            <!-- SIDEBAR CONTROLS (4 COLS) -->
            <div class="lg:col-span-4 space-y-6">

                <!-- Publishing Controls -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2">
                        Publishing Status
                    </h3>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Workflow State <span class="text-red-600">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] font-semibold">
                            <option value="published" {{ old('status', $article->status) === 'published' ? 'selected' : '' }}>● Published Live</option>
                            <option value="in_review" {{ old('status', $article->status) === 'in_review' ? 'selected' : '' }}>● In Editorial Review</option>
                            <option value="draft" {{ old('status', $article->status) === 'draft' ? 'selected' : '' }}>● Working Draft</option>
                            <option value="scheduled" {{ old('status', $article->status) === 'scheduled' ? 'selected' : '' }}>● Scheduled Publication</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Scheduled Date &amp; Time
                        </label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $article->scheduled_at?->format('Y-m-d\TH:i')) }}"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    </div>

                    <!-- Editorial Priority Toggles -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="is_breaking" value="1" {{ old('is_breaking', $article->is_breaking) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                            <span>⚡ Flag as Breaking News Alert</span>
                        </label>

                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $article->is_featured) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-[#1b52bc] focus:ring-blue-500">
                            <span>★ Feature on Homepage Hero</span>
                        </label>

                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="is_editors_pick" value="1" {{ old('is_editors_pick', $article->is_editors_pick) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>✦ Mark as Editor's Choice Pick</span>
                        </label>

                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="allow_comments" value="1" {{ old('allow_comments', $article->allow_comments) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                            <span>Enable Reader Comments</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 space-y-2">
                        <button type="submit" class="w-full py-3 bg-[#1b52bc] hover:bg-blue-800 text-white font-bold text-xs uppercase tracking-wider rounded shadow transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>

                <!-- Channel & Taxonomy Selection -->
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2">
                        Channel &amp; Categorization
                    </h3>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Primary Section <span class="text-red-600">*</span>
                        </label>
                        <select name="category_id" x-model="categoryId" required class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dynamic Subcategory Cascade -->
                    <div x-show="availableSubcategories.length > 0" x-transition>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Specific Subcategory / Desk
                        </label>
                        <select name="subcategory_id" x-model="subcategoryId" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                            <option value="">Select Subcategory (Optional)...</option>
                            <template x-for="sub in availableSubcategories" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name" :selected="sub.id == subcategoryId"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Reporter / Byline <span class="text-red-600">*</span>
                        </label>
                        <select name="user_id" required class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ old('user_id', $article->user_id) == $author->id ? 'selected' : '' }}>
                                    {{ $author->name }} ({{ $author->job_title ?? 'Journalist' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($tags->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Story Tags
                            </label>
                            <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 border border-slate-200 rounded text-xs">
                                @php $activeTagIds = old('tags', $article->tags->pluck('id')->toArray()); @endphp
                                @foreach($tags as $tag)
                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-slate-50 p-1 rounded">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                            {{ in_array($tag->id, $activeTagIds) ? 'checked' : '' }}
                                            class="rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                                        <span class="text-slate-700">{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Danger Zone (Delete) -->
                <div class="bg-red-50/60 border border-red-200 rounded-lg p-5 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-red-900">Danger Zone</h4>
                    <p class="text-[11px] text-red-700">Permanently delete this story from the platform and archive.</p>
                    <button type="button" onclick="if(confirm('Are you sure you want to permanently delete this story?')) document.getElementById('deleteStoryForm').submit();"
                        class="w-full py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded transition">
                        Delete Story
                    </button>
                </div>

            </div>

        </div>
    </form>

    <!-- Hidden standalone form for deletion -->
    <form id="deleteStoryForm" method="POST" action="{{ route('admin.articles.destroy', $article->slug) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
