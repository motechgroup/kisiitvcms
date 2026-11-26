<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Listing columns for sidebar and related story cards.
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
     * Display a published news article.
     */
    public function show(Request $request, Article $article): View
    {
        // Only allow published articles unless user is an authenticated editorial staff
        if ($article->status !== 'published') {
            abort_unless(auth()->check(), 404);
        }

        // Record view analytics after the response is flushed to the browser
        $articleId = $article->id;
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $referer = $request->header('referer');

        app()->terminating(function () use ($articleId, $ipAddress, $userAgent, $referer): void {
            Article::whereKey($articleId)->increment('view_count');
            ArticleView::create([
                'article_id' => $articleId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'referer' => $referer,
                'viewed_at' => now(),
            ]);
        });

        $article->load(['category', 'author', 'tags']);

        // Cached Navigation Categories
        $navCategories = Category::cachedNavCategories();

        // Related stories in same category
        $relatedArticles = Article::select(self::LISTING_COLUMNS)
            ->published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->with(['category:id,name,slug', 'author:id,name,username,avatar_path'])
            ->latest('published_at')
            ->take(4)
            ->get();

        // Trending / Most read stories for sidebar
        $mostRead = Article::select(self::LISTING_COLUMNS)
            ->published()
            ->where('id', '!=', $article->id)
            ->with(['category:id,name,slug', 'author:id,name,username,avatar_path'])
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        // Latest wire items
        $latestWire = Article::select(self::LISTING_COLUMNS)
            ->published()
            ->where('id', '!=', $article->id)
            ->with(['category:id,name,slug', 'author:id,name,username,avatar_path'])
            ->latest('published_at')
            ->take(5)
            ->get();

        // Sidebar Ad
        $sidebarAd = AdPlacement::where('slug', 'sidebar-mrec')->first()?->activeAdvertisement();

        return view('articles.show', compact(
            'article',
            'navCategories',
            'relatedArticles',
            'mostRead',
            'latestWire',
            'sidebarAd'
        ));
    }
}
