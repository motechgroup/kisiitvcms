<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\LiveBlog;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Listing columns needed on homepage cards (omits large HTML content bodies).
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
        'subcategory_id',
        'user_id',
        'published_at',
        'view_count',
        'is_featured',
        'is_breaking',
        'article_type',
        'reading_time_minutes',
        'video_url',
        'status',
    ];

    /**
     * Display the newsroom public homepage.
     */
    public function index(): View
    {
        $navCategories = Category::cachedNavCategories();

        $homeData = Cache::remember('homepage_data_v1', 120, function (): array {
            // 1. Breaking News ticker items
            $breakingNews = BreakingNews::active()->get();

            // 2. Lead Story
            $leadSection = HomepageSection::where('slug', 'lead-story')->active()->first();
            $leadStory = $leadSection?->articles()
                ->select(array_map(fn (string $col) => "articles.{$col}", self::LISTING_COLUMNS))
                ->published()
                ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                ->first();

            if (! $leadStory) {
                $leadStory = Article::select(self::LISTING_COLUMNS)
                    ->published()
                    ->where('is_featured', true)
                    ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                    ->latest('published_at')
                    ->first();
            }

            $excludeIds = $leadStory ? [$leadStory->id] : [];

            // 3. Secondary Featured Stories (Top 3)
            $secondaryFeatured = Article::select(self::LISTING_COLUMNS)
                ->published()
                ->whereNotIn('id', $excludeIds)
                ->where('is_featured', true)
                ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                ->latest('published_at')
                ->take(3)
                ->get();

            $excludeIds = array_merge($excludeIds, $secondaryFeatured->pluck('id')->all());

            // 4. Latest News Wire
            $latestNews = Article::select(self::LISTING_COLUMNS)
                ->published()
                ->whereNotIn('id', $excludeIds)
                ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                ->latest('published_at')
                ->take(8)
                ->get();

            // 5. Most Read Stories (Trending / Top Views)
            $mostRead = Article::select(self::LISTING_COLUMNS)
                ->published()
                ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                ->orderByDesc('view_count')
                ->take(6)
                ->get();

            // 6. Category-specific sections (batch category lookup in 1 query)
            $keyCategories = [
                'kenya', 'politics', 'counties', 'business', 'world',
                'crime', 'education', 'technology', 'sports', 'entertainment',
                'lifestyle', 'opinion', 'video',
            ];

            $categoriesBySlug = Category::whereIn('slug', $keyCategories)->get()->keyBy('slug');
            $categoryArticles = [];

            foreach ($keyCategories as $slug) {
                $cat = $categoriesBySlug->get($slug);
                if ($cat) {
                    $categoryArticles[$slug] = [
                        'category' => $cat,
                        'articles' => Article::select(self::LISTING_COLUMNS)
                            ->published()
                            ->forCategory($cat->id)
                            ->with(['category:id,name,slug', 'author:id,name,username,avatar'])
                            ->latest('published_at')
                            ->take(4)
                            ->get(),
                    ];
                }
            }

            // 7. Active Live Blog
            $liveBlog = LiveBlog::active()
                ->with(['updates' => fn ($q) => $q->with('author:id,name,username,avatar')->take(5)])
                ->latest('started_at')
                ->first();

            // 8. Trending Tags
            $trendingTags = Tag::withCount('articles')
                ->orderByDesc('articles_count')
                ->take(8)
                ->get();

            // 9. Advertisements (single batched placement lookup)
            $adPlacements = AdPlacement::whereIn('slug', [
                'header-leaderboard',
                'sidebar-mrec',
                'content-billboard',
            ])->get()->keyBy('slug');

            $headerAd = $adPlacements->get('header-leaderboard')?->activeAdvertisement();
            $sidebarAd = $adPlacements->get('sidebar-mrec')?->activeAdvertisement();
            $contentAd = $adPlacements->get('content-billboard')?->activeAdvertisement();

            return compact(
                'breakingNews',
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
            );
        });

        return view('home', array_merge($homeData, [
            'navCategories' => $navCategories,
        ]));
    }
}
