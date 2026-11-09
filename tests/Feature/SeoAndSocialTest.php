<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndSocialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kisiitv_scraped_social_handles_are_rendered_on_homepage(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // Scraped social profiles
        $response->assertSee('https://www.youtube.com/@kisiitv', false);
        $response->assertSee('https://www.facebook.com/KisiitvAmandegere', false);
        $response->assertSee('https://www.tiktok.com/@kevinombworisnr', false);
        $response->assertSee('https://x.com/kisiitv', false);
        $response->assertSee('https://wa.me/254726547363', false);
        // Contact details
        $response->assertSee('kisiitelevision@gmail.com');
        $response->assertSee('+254 726 547 363');
        $response->assertSee("Amandegere Y'Omogusii");
    }

    public function test_homepage_contains_rich_seo_tags_and_structured_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('<meta name="robots" content="index, follow', false);
        $response->assertSee('<meta name="geo.region" content="KE-16">', false);
        $response->assertSee('property="og:site_name"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('NewsMediaOrganization', false);
        $response->assertSee('WebSite', false);
        $response->assertSee('https://www.youtube.com/@kisiitv', false);
    }

    public function test_sitemap_xml_can_be_generated_and_is_valid(): void
    {
        $response = $this->get(route('seo.sitemap'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->assertSee('<urlset', false);
        $response->assertSee('<loc>'.url('/').'</loc>', false);

        $category = Category::where('is_active', true)->first();
        if ($category) {
            $response->assertSee(route('categories.show', $category->slug), false);
        }

        $article = Article::where('status', 'published')->first();
        if ($article) {
            $response->assertSee(route('articles.show', $article->slug), false);
        }
    }

    public function test_rss_feed_can_be_generated_and_is_valid(): void
    {
        $response = $this->get(route('seo.rss'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->assertSee('<rss version="2.0"', false);
        $response->assertSee('<channel>', false);
        $response->assertSee('Amandegere', false);

        $article = Article::where('status', 'published')->first();
        if ($article) {
            $response->assertSee($article->title, false);
            $response->assertSee(route('articles.show', $article->slug), false);
        }
    }

    public function test_article_page_contains_news_article_schema_and_rich_share_tools(): void
    {
        $article = Article::where('status', 'published')->firstOrFail();

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('NewsArticle', false);
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('<link rel="canonical" href="'.route('articles.show', $article->slug).'">', false);
        $response->assertSee('property="article:published_time"', false);
        // Social share tools
        $response->assertSee('api.whatsapp.com/send', false);
        $response->assertSee('facebook.com/sharer/sharer.php', false);
        $response->assertSee('twitter.com/intent/tweet', false);
        $response->assertSee('t.me/share/url', false);
    }

    public function test_category_page_contains_breadcrumbs_schema_and_canonical_url(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();

        $response = $this->get(route('categories.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('<link rel="canonical" href="'.route('categories.show', $category->slug).'">', false);
    }
}
