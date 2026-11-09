<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Comment;
use App\Models\LiveBlog;
use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the newsroom super administrative dashboard.
     */
    public function index(): View
    {
        // 1. Editorial Pipeline Metrics
        $publishedCount = Article::published()->count();
        $draftCount = Article::where('status', 'draft')->count();
        $inReviewCount = Article::where('status', 'in_review')->count();
        $scheduledCount = Article::where('status', 'scheduled')->count();
        $totalViews = (int) Article::sum('view_count');
        $todayPublishedCount = Article::published()
            ->whereDate('published_at', today())
            ->count();
        $leadStoriesCount = Article::where('is_featured', true)->count();

        // 2. Categories & Navigation Menu
        $totalCategories = Category::count();
        $menuCategoriesCount = Category::roots()->menu()->count();
        $categories = Category::roots()
            ->withCount('articles')
            ->withSum('articles', 'view_count')
            ->orderBy('sort_order')
            ->get();

        // 3. Breaking News & Live Coverage
        $breakingNewsItems = BreakingNews::with('article')
            ->orderByDesc('is_active')
            ->orderByDesc('priority')
            ->latest()
            ->take(6)
            ->get();
        $activeBreakingCount = BreakingNews::active()->count();
        $activeLiveBlog = LiveBlog::active()->withCount('updates')->first();

        // 4. Articles by Workflow Queues
        $reviewQueue = Article::where('status', 'in_review')
            ->with(['category', 'author'])
            ->latest('updated_at')
            ->take(6)
            ->get();

        $recentPublished = Article::published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $recentDrafts = Article::where('status', 'draft')
            ->with(['category', 'author'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $topArticles = Article::published()
            ->with(['category', 'author'])
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        // 5. Staff & Newsroom Desks
        $staffUsers = User::with('roles')
            ->where('is_active', true)
            ->latest()
            ->take(6)
            ->get();
        $totalStaffCount = User::where('is_active', true)->count();
        $rolesWithCount = Role::withCount('users')->get();

        // 6. Monetization & Audience
        $totalAdsCount = Advertisement::where('is_active', true)->count();
        $subscribersCount = NewsletterSubscriber::where('is_active', true)->count();
        $commentsPendingCount = Comment::pending()->count();
        $totalCommentsCount = Comment::count();

        // 7. System & Environment Telemetry
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => config('database.default'),
            'server_time' => now()->format('D, d M Y - H:i:s T'),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
        ];

        return view('admin.dashboard', compact(
            'publishedCount',
            'draftCount',
            'inReviewCount',
            'scheduledCount',
            'totalViews',
            'todayPublishedCount',
            'leadStoriesCount',
            'totalCategories',
            'menuCategoriesCount',
            'categories',
            'breakingNewsItems',
            'activeBreakingCount',
            'activeLiveBlog',
            'reviewQueue',
            'recentPublished',
            'recentDrafts',
            'topArticles',
            'staffUsers',
            'totalStaffCount',
            'rolesWithCount',
            'totalAdsCount',
            'subscribersCount',
            'commentsPendingCount',
            'totalCommentsCount',
            'systemInfo'
        ));
    }

    /**
     * Dispatch a fast breaking news alert banner to the live broadcast ticker.
     */
    public function storeBreakingNews(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alert_text' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'priority' => 'nullable|integer|min:1|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        BreakingNews::create([
            'alert_text' => $validated['alert_text'],
            'title' => $validated['title'] ?? Str::limit($validated['alert_text'], 50),
            'url' => $validated['url'] ?? null,
            'priority' => $validated['priority'] ?? 5,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => now(),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Breaking news alert dispatched live to the ticker!');
    }

    /**
     * Toggle active state of a breaking news item.
     */
    public function toggleBreakingNews(BreakingNews $breakingNews): RedirectResponse
    {
        $breakingNews->update([
            'is_active' => ! $breakingNews->is_active,
        ]);

        $status = $breakingNews->is_active ? 'activated on live site' : 'paused';

        return redirect()->route('admin.dashboard')->with('success', "Breaking news ticker item {$status}.");
    }

    /**
     * Remove a breaking news item.
     */
    public function destroyBreakingNews(BreakingNews $breakingNews): RedirectResponse
    {
        $breakingNews->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Breaking news ticker item removed.');
    }

    /**
     * Quick-create an editorial article directly from the dashboard.
     */
    public function quickCreateArticle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'summary' => 'required|string|max:500',
            'status' => 'required|in:draft,in_review,published',
            'featured_image' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $wordCount = str_word_count($validated['summary']);
        $readingTime = max(1, (int) ceil($wordCount / 200));

        Article::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'],
            'content' => '<p class="lead text-lg font-medium text-slate-800 mb-4">'.e($validated['summary']).'</p><p>Full report filed by the Kisii TV Editorial Bureau. Updates will follow as the story develops.</p>',
            'category_id' => $validated['category_id'],
            'user_id' => auth()->id(),
            'status' => $validated['status'],
            'featured_image' => ($validated['featured_image'] ?? null) ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1200&auto=format&fit=crop&q=80',
            'image_caption' => 'Newsroom coverage dispatch - Kisii TV',
            'image_credit' => 'Kisii TV Photo Desk',
            'is_featured' => $request->boolean('is_featured'),
            'reading_time_minutes' => $readingTime,
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()->route('admin.dashboard')->with('success', "Story '{$validated['title']}' created successfully as {$validated['status']}!");
    }

    /**
     * Instantly approve and publish a story from the review queue.
     */
    public function publishArticle(Article $article): RedirectResponse
    {
        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);

        return redirect()->route('admin.dashboard')->with('success', "Story '{$article->title}' is now published live to Kisii TV!");
    }
}
