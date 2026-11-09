<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate dynamic XML sitemap for search engines.
     */
    public function sitemap(): Response
    {
        $categories = Category::where('is_active', true)->get();
        $articles = Article::where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->limit(500)
            ->get();

        $content = view('seo.sitemap', [
            'categories' => $categories,
            'articles' => $articles,
            'siteName' => SiteSetting::get('site_name', 'Kisii TV'),
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Generate RSS 2.0 / Media RSS feed for news syndication and aggregators.
     */
    public function rss(): Response
    {
        $articles = Article::where('status', 'published')
            ->whereNotNull('published_at')
            ->with(['category', 'author'])
            ->orderBy('published_at', 'desc')
            ->limit(30)
            ->get();

        $content = view('seo.rss', [
            'articles' => $articles,
            'siteName' => SiteSetting::get('site_name', 'Kisii TV'),
            'siteTagline' => SiteSetting::get('site_tagline', "Amandegere Y'Omogusii"),
            'siteDescription' => SiteSetting::get('site_description', 'Latest breaking news and investigative journalism from Kisii TV.'),
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
