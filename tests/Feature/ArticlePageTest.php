<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Database\Seeders\NewsContentSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            SiteSettingSeeder::class,
            NewsContentSeeder::class,
        ]);
    }

    public function test_article_page_can_be_rendered_and_increments_views(): void
    {
        /** @var Article $article */
        $article = Article::where('status', 'published')->firstOrFail();
        $initialViews = $article->view_count;

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee($article->title);
        $response->assertSee($article->author->name);
        $response->assertSee($article->category->name);

        $article->refresh();
        $this->assertEquals($initialViews + 1, $article->view_count);
    }

    public function test_unpublished_article_returns_404(): void
    {
        /** @var Article $article */
        $article = Article::where('status', 'published')->firstOrFail();
        $article->update([
            'status' => 'draft',
        ]);

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertStatus(404);
    }

    public function test_category_archive_page_can_be_rendered(): void
    {
        /** @var Category $category */
        $category = Category::where('slug', 'kenya')->firstOrFail();

        $response = $this->get(route('categories.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee($category->name);
    }

    public function test_homepage_contains_no_hash_article_or_category_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('#article-');
        $response->assertDontSee('#category-');
        $response->assertDontSee('#subcategory-');
    }
}
