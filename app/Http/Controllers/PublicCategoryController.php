<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PublicCategoryController extends Controller
{
    /**
     * Listing columns needed on category archive cards.
     *
     * @var array<int, string>
     */
    private const LISTING_COLUMNS = [
        'id',
        'title',
        'slug',
        'summary',
        'featured_image',
        'category_id',
        'user_id',
        'published_at',
        'view_count',
        'status',
    ];

    /**
     * Display a category archive / section wire.
     */
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        // Include subcategories in query
        $categoryIds = Category::where('id', $category->id)
            ->orWhere('parent_id', $category->id)
            ->pluck('id');

        $articles = Article::select(self::LISTING_COLUMNS)
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
            ->latest('published_at')
            ->paginate(12);

        $subcategories = $category->children()->where('is_active', true)->get();

        // Cached Navigation Categories
        $navCategories = Category::cachedNavCategories();

        // Cached Trending stories
        $mostRead = Cache::remember('sidebar_most_read', 180, function () {
            return Article::select(self::LISTING_COLUMNS)
                ->published()
                ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                ->orderByDesc('view_count')
                ->take(6)
                ->get();
        })->take(5)->values();

        // Cached Sidebar Ad
        $sidebarAd = Cache::remember('ad_sidebar_mrec', 180, function () {
            return AdPlacement::where('slug', 'sidebar-mrec')->first()?->activeAdvertisement();
        });

        return view('categories.show', compact(
            'category',
            'articles',
            'subcategories',
            'navCategories',
            'mostRead',
            'sidebarAd'
        ));
    }
}
