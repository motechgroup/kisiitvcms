<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\LiveBlog;
use App\Models\Tag;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the newsroom public homepage.
     */
    public function index(): View
    {
        // 1. Breaking News ticker items
        $breakingNews = BreakingNews::active()->get();

        // 2. Navigation Categories (Root categories with active children)
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        // 3. Lead Story
        $leadSection = HomepageSection::where('slug', 'lead-story')->active()->first();
        $leadStory = $leadSection?->articles()
            ->published()
            ->with(['category', 'author'])
            ->first();

        if (! $leadStory) {
            $leadStory = Article::published()
                ->where('is_featured', true)
                ->with(['category', 'author'])
                ->latest('published_at')
                ->first();
        }

        $excludeIds = $leadStory ? [$leadStory->id] : [];

        // 4. Secondary Featured Stories (Top 3)
        $secondaryFeatured = Article::published()
            ->whereNotIn('id', $excludeIds)
            ->where('is_featured', true)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $excludeIds = array_merge($excludeIds, $secondaryFeatured->pluck('id')->all());

        // 5. Latest News Wire
        $latestNews = Article::published()
            ->whereNotIn('id', $excludeIds)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(8)
            ->get();

        // 6. Most Read Stories (Trending / Top Views)
        $mostRead = Article::published()
            ->with(['category', 'author'])
            ->orderByDesc('view_count')
            ->take(6)
            ->get();

        // 7. Category specific sections
        $categoryArticles = [];
        $keyCategories = [
            'kenya', 'politics', 'counties', 'business', 'world',
            'crime', 'education', 'technology', 'sports', 'entertainment',
            'lifestyle', 'opinion', 'video',
        ];

        foreach ($keyCategories as $slug) {
            $cat = Category::where('slug', $slug)->first();
            if ($cat) {
                $categoryArticles[$slug] = [
                    'category' => $cat,
                    'articles' => Article::published()
                        ->forCategory($cat->id)
                        ->with(['category', 'author'])
                        ->latest('published_at')
                        ->take(4)
                        ->get(),
                ];
            }
        }

        // 8. Active Live Blog
        $liveBlog = LiveBlog::active()
            ->with(['updates' => fn ($q) => $q->with('author')->take(5)])
            ->latest('started_at')
            ->first();

        // 9. Trending Tags
        $trendingTags = Tag::withCount('articles')
            ->orderByDesc('articles_count')
            ->take(8)
            ->get();

        // 10. Advertisements
        $headerAd = AdPlacement::where('slug', 'header-leaderboard')->first()?->activeAdvertisement();
        $sidebarAd = AdPlacement::where('slug', 'sidebar-mrec')->first()?->activeAdvertisement();
        $contentAd = AdPlacement::where('slug', 'content-billboard')->first()?->activeAdvertisement();

        return view('home', compact(
            'breakingNews',
            'navCategories',
            'leadStory',
            'secondaryFeatured',
            'latestNews',
            'mostRead',
            'categoryArticles',
            'liveBlog',
            'trendingTags',
            'headerAd',
            'sidebarAd',
            'contentAd'
        ));
    }
}
