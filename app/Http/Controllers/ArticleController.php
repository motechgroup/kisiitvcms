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
     * Display a published news article.
     */
    public function show(Request $request, Article $article): View
    {
        // Only allow published articles unless user is an authenticated editorial staff
        if ($article->status !== 'published') {
            abort_unless(auth()->check(), 404);
        }

        // Increment view count
        $article->increment('view_count');

        // Record analytical view
        ArticleView::create([
            'article_id' => $article->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'viewed_at' => now(),
        ]);

        $article->load(['category', 'author', 'tags']);

        // Navigation Categories
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        // Related stories in same category
        $relatedArticles = Article::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(4)
            ->get();

        // Trending / Most read stories for sidebar
        $mostRead = Article::published()
            ->where('id', '!=', $article->id)
            ->with(['category', 'author'])
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        // Latest wire items
        $latestWire = Article::published()
            ->where('id', '!=', $article->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(5)
            ->get();

        // Sidebar Ad
        $sidebarAdPlacement = AdPlacement::where('slug', 'sidebar-mrec')->first();
        $sidebarAd = $sidebarAdPlacement?->advertisements()->active()->inRandomOrder()->first();

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
