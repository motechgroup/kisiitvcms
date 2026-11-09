<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Display a paginated listing of newsroom articles with filtering, sorting, and search.
     */
    public function index(Request $request): View
    {
        $query = Article::with(['category', 'subcategory', 'author']);

        // Search in headline, subtitle, summary, or content
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Filter by Category
        if ($request->filled('category_id')) {
            $catId = $request->input('category_id');
            $query->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                    ->orWhere('subcategory_id', $catId);
            });
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by Author
        if ($request->filled('author_id')) {
            $query->where('user_id', $request->input('author_id'));
        }

        // Filter by Article Type
        if ($request->filled('article_type')) {
            $query->where('article_type', $request->input('article_type'));
        }

        // Filter by Flag (breaking, featured, editors_pick)
        if ($request->filled('flag')) {
            match ($request->input('flag')) {
                'breaking' => $query->where('is_breaking', true),
                'featured' => $query->where('is_featured', true),
                'editors_pick' => $query->where('is_editors_pick', true),
                default => null,
            };
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest('published_at')->oldest('id'),
            'views' => $query->orderByDesc('view_count'),
            'title' => $query->orderBy('title'),
            'updated' => $query->latest('updated_at'),
            default => $query->latest('published_at')->latest('id'),
        };

        $articles = $query->paginate(15)->withQueryString();

        $categories = Category::roots()->with('children')->orderBy('name')->get();
        $authors = User::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'in_review' => Article::where('status', 'in_review')->count(),
            'draft' => Article::where('status', 'draft')->count(),
            'breaking' => Article::where('is_breaking', true)->count(),
            'featured' => Article::where('is_featured', true)->count(),
        ];

        return view('admin.articles.index', compact('articles', 'categories', 'authors', 'stats'));
    }

    /**
     * Show the form for creating a new editorial article.
     */
    public function create(): View
    {
        $categories = Category::roots()->with('children')->orderBy('name')->get();
        $authors = User::where('is_active', true)->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.articles.create', compact('categories', 'authors', 'tags'));
    }

    /**
     * Store a newly created editorial article in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'article_type' => 'nullable|in:standard,video,opinion,analysis,investigation,gallery',
            'summary' => 'required|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_caption' => 'nullable|string|max:255',
            'image_credit' => 'nullable|string|max:150',
            'status' => 'required|in:draft,in_review,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'is_editors_pick' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
            'video_url' => 'nullable|url|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:500',
            'tags' => 'nullable',
        ]);

        // Handle uploaded image file if present
        $featuredImagePath = ($validated['featured_image'] ?? null) ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1200&auto=format&fit=crop&q=80';
        if ($request->hasFile('featured_image_file')) {
            $uploadDir = public_path('uploads/articles');
            File::ensureDirectoryExists($uploadDir);
            $file = $request->file('featured_image_file');
            $fileName = uniqid('art_', true).'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $featuredImagePath = asset('uploads/articles/'.$fileName);
        }

        // Unique Slug Generation
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        // Reading time & word count
        $wordCount = str_word_count(strip_tags($validated['content']));
        $readingTime = max(1, (int) ceil($wordCount / 200));

        $publishedAt = null;
        if ($validated['status'] === 'published') {
            $publishedAt = now();
        } elseif ($validated['status'] === 'scheduled' && ! empty($validated['scheduled_at'])) {
            $publishedAt = $validated['scheduled_at'];
        }

        $article = Article::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'subtitle' => $validated['subtitle'] ?? null,
            'category_id' => $validated['category_id'],
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'user_id' => $validated['user_id'],
            'article_type' => $validated['article_type'] ?? 'standard',
            'summary' => $validated['summary'],
            'content' => $validated['content'],
            'featured_image' => $featuredImagePath,
            'image_caption' => $validated['image_caption'] ?? null,
            'image_credit' => $validated['image_credit'] ?? null,
            'status' => $validated['status'],
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'published_at' => $publishedAt,
            'is_featured' => $request->boolean('is_featured'),
            'is_breaking' => $request->boolean('is_breaking'),
            'is_editors_pick' => $request->boolean('is_editors_pick'),
            'allow_comments' => $request->boolean('allow_comments', true),
            'reading_time_minutes' => $readingTime,
            'video_url' => $validated['video_url'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
        ]);

        $this->syncTags($article, $request->input('tags'));

        return redirect()->route('admin.articles.index')->with('success', "Story '{$article->title}' was published successfully!");
    }

    /**
     * Show the form for editing an existing editorial article.
     */
    public function edit(Article $article): View
    {
        $categories = Category::roots()->with('children')->orderBy('name')->get();
        $authors = User::where('is_active', true)->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.articles.edit', compact('article', 'categories', 'authors', 'tags'));
    }

    /**
     * Update the specified editorial article in storage.
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'article_type' => 'nullable|in:standard,video,opinion,analysis,investigation,gallery',
            'summary' => 'required|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_caption' => 'nullable|string|max:255',
            'image_credit' => 'nullable|string|max:150',
            'status' => 'required|in:draft,in_review,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'is_editors_pick' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
            'video_url' => 'nullable|url|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:500',
            'tags' => 'nullable',
        ]);

        // Featured image resolution (Uploaded file > submitted URL > existing image)
        $featuredImagePath = $article->featured_image;
        if ($request->hasFile('featured_image_file')) {
            $uploadDir = public_path('uploads/articles');
            File::ensureDirectoryExists($uploadDir);
            $file = $request->file('featured_image_file');
            $fileName = uniqid('art_', true).'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $featuredImagePath = asset('uploads/articles/'.$fileName);
        } elseif (! empty($validated['featured_image'])) {
            $featuredImagePath = $validated['featured_image'];
        }

        $publishedAt = $article->published_at;
        if ($validated['status'] === 'published' && ! $publishedAt) {
            $publishedAt = now();
        }

        $wordCount = str_word_count(strip_tags($validated['content']));
        $readingTime = max(1, (int) ceil($wordCount / 200));

        $article->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'category_id' => $validated['category_id'],
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'user_id' => $validated['user_id'],
            'article_type' => $validated['article_type'] ?? ($article->article_type ?? 'standard'),
            'summary' => $validated['summary'],
            'content' => $validated['content'],
            'featured_image' => $featuredImagePath,
            'image_caption' => $validated['image_caption'] ?? null,
            'image_credit' => $validated['image_credit'] ?? null,
            'status' => $validated['status'],
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'published_at' => $publishedAt,
            'is_featured' => $request->boolean('is_featured'),
            'is_breaking' => $request->boolean('is_breaking'),
            'is_editors_pick' => $request->boolean('is_editors_pick'),
            'allow_comments' => $request->boolean('allow_comments', true),
            'reading_time_minutes' => $readingTime,
            'video_url' => $validated['video_url'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
        ]);

        $this->syncTags($article, $request->input('tags'));

        return redirect()->route('admin.articles.index')->with('success', "Story '{$article->title}' was updated successfully!");
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $title = $article->title;
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', "Story '{$title}' has been deleted.");
    }

    /**
     * Perform bulk actions on selected articles.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:publish,draft,in_review,feature,unfeature,breaking,unbreaking,delete',
            'selected_articles' => 'required|array|min:1',
            'selected_articles.*' => 'exists:articles,id',
        ]);

        $ids = $validated['selected_articles'];
        $count = count($ids);

        switch ($validated['action']) {
            case 'publish':
                Article::whereIn('id', $ids)->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                $msg = "{$count} articles published live successfully.";
                break;

            case 'draft':
                Article::whereIn('id', $ids)->update(['status' => 'draft']);
                $msg = "{$count} articles moved to draft.";
                break;

            case 'in_review':
                Article::whereIn('id', $ids)->update(['status' => 'in_review']);
                $msg = "{$count} articles submitted to editorial review.";
                break;

            case 'feature':
                Article::whereIn('id', $ids)->update(['is_featured' => true]);
                $msg = "{$count} articles marked as Featured stories.";
                break;

            case 'unfeature':
                Article::whereIn('id', $ids)->update(['is_featured' => false]);
                $msg = "{$count} articles removed from Featured.";
                break;

            case 'breaking':
                Article::whereIn('id', $ids)->update(['is_breaking' => true]);
                $msg = "{$count} articles marked as Breaking News.";
                break;

            case 'unbreaking':
                Article::whereIn('id', $ids)->update(['is_breaking' => false]);
                $msg = "{$count} articles removed from Breaking News.";
                break;

            case 'delete':
                Article::whereIn('id', $ids)->delete();
                $msg = "{$count} articles were deleted.";
                break;

            default:
                $msg = 'Action processed.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Duplicate an existing article as a working draft.
     */
    public function clone(Article $article): RedirectResponse
    {
        $newTitle = $article->title.' (Copy)';
        $newSlug = Str::slug($newTitle);
        $counter = 1;
        while (Article::where('slug', $newSlug)->exists()) {
            $newSlug = Str::slug($newTitle).'-'.$counter;
            $counter++;
        }

        $clone = $article->replicate([
            'view_count',
            'published_at',
            'scheduled_at',
        ]);

        $clone->title = $newTitle;
        $clone->slug = $newSlug;
        $clone->status = 'draft';
        $clone->user_id = auth()->id();
        $clone->is_breaking = false;
        $clone->is_featured = false;
        $clone->save();

        if ($article->tags->isNotEmpty()) {
            $clone->tags()->sync($article->tags->pluck('id'));
        }

        return redirect()->route('admin.articles.edit', $clone->slug)->with('success', "Story cloned as a draft: '{$clone->title}'");
    }

    /**
     * Toggle the publishing status between published and draft.
     */
    public function toggleStatus(Article $article): RedirectResponse
    {
        $newStatus = $article->status === 'published' ? 'draft' : 'published';
        $article->update([
            'status' => $newStatus,
            'published_at' => $newStatus === 'published' ? ($article->published_at ?? now()) : $article->published_at,
        ]);

        return redirect()->back()->with('success', "Story status updated to {$newStatus}.");
    }

    /**
     * Toggle the featured/lead status of an article.
     */
    public function toggleFeatured(Article $article): RedirectResponse
    {
        $article->update([
            'is_featured' => ! $article->is_featured,
        ]);

        $status = $article->is_featured ? 'marked as Featured' : 'unmarked from Featured';

        return redirect()->back()->with('success', "Story {$status}.");
    }

    /**
     * Toggle the breaking alert status of an article.
     */
    public function toggleBreaking(Article $article): RedirectResponse
    {
        $article->update([
            'is_breaking' => ! $article->is_breaking,
        ]);

        $status = $article->is_breaking ? 'flagged as Breaking News' : 'removed from Breaking News';

        return redirect()->back()->with('success', "Story {$status}.");
    }

    /**
     * Toggle the editor's pick flag of an article.
     */
    public function toggleEditorsPick(Article $article): RedirectResponse
    {
        $article->update([
            'is_editors_pick' => ! $article->is_editors_pick,
        ]);

        $status = $article->is_editors_pick ? 'selected as Editor\'s Pick' : 'removed from Editor\'s Picks';

        return redirect()->back()->with('success', "Story {$status}.");
    }

    /**
     * Synchronize tags from either an array of tag IDs/names or comma-separated string.
     */
    protected function syncTags(Article $article, mixed $tagsInput): void
    {
        if (empty($tagsInput)) {
            return;
        }

        $tagIds = [];

        if (is_string($tagsInput)) {
            $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                );
                $tagIds[] = $tag->id;
            }
        } elseif (is_array($tagsInput)) {
            foreach ($tagsInput as $item) {
                if (is_numeric($item)) {
                    $tagIds[] = (int) $item;
                } elseif (is_string($item) && ! empty(trim($item))) {
                    $tag = Tag::firstOrCreate(
                        ['slug' => Str::slug($item)],
                        ['name' => trim($item)]
                    );
                    $tagIds[] = $tag->id;
                }
            }
        }

        if (! empty($tagIds)) {
            $article->tags()->sync($tagIds);
        }
    }
}
