<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Database\Seeders\NewsContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed([
            CategorySeeder::class,
            NewsContentSeeder::class,
        ]);

        $this->withoutExceptionHandling();

        // Exercise real file cache serialization with serializable_classes = false (production setting)
        config([
            'cache.default' => 'file',
            'cache.serializable_classes' => false,
        ]);
        Cache::store('file')->flush();

        // First request warms the cache; second request reads unserialized cache entries
        $this->get('/')->assertStatus(200);
        $this->get('/')->assertStatus(200);

        $article = Article::published()->first();
        if ($article) {
            $this->get(route('articles.show', $article))->assertStatus(200);
            $this->get(route('articles.show', $article))->assertStatus(200);
        }

        $category = Category::where('is_active', true)->first();
        if ($category) {
            $this->get(route('categories.show', $category))->assertStatus(200);
            $this->get(route('categories.show', $category))->assertStatus(200);
        }
    }
}
