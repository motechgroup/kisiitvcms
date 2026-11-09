<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            SiteSettingSeeder::class,
        ]);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@kisiitv.com',
            'name' => 'Lead Architect',
            'is_active' => true,
        ]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $this->superAdmin->roles()->attach($superAdminRole->id);
        }
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_enhanced_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Super Administrator');
        $response->assertSee('Welcome back, Lead Architect');
        $response->assertSee('Breaking News Live Broadcast Ticker Controller');
        $response->assertSee('Editorial Publishing Station');
        $response->assertSee('Broadcast Channel & News Category Coverage');
        $response->assertSee('Platform Infrastructure');
    }

    public function test_super_admin_can_dispatch_breaking_news(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.breaking-news.store'), [
            'title' => 'FLASH',
            'alert_text' => 'FLASH: National Assembly passes revenue formula',
            'priority' => 9,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('breaking_news', [
            'alert_text' => 'FLASH: National Assembly passes revenue formula',
            'priority' => 9,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_toggle_breaking_news(): void
    {
        $item = BreakingNews::create([
            'title' => 'Urgent Notice',
            'alert_text' => 'Urgent Breaking Ticker Notice',
            'priority' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.breaking-news.toggle', $item->id));

        $response->assertRedirect(route('admin.dashboard'));
        $item->refresh();
        $this->assertFalse($item->is_active);
    }

    public function test_super_admin_can_quick_create_article(): void
    {
        $category = Category::first();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.articles.quick-create'), [
            'title' => 'Devolution Summit Concludes in Kisii',
            'category_id' => $category->id,
            'summary' => 'Governors agree on joint economic development projects across the lake region.',
            'status' => 'published',
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('articles', [
            'title' => 'Devolution Summit Concludes in Kisii',
            'category_id' => $category->id,
            'status' => 'published',
            'is_featured' => true,
        ]);
    }

    public function test_super_admin_can_publish_article_from_review_queue(): void
    {
        $category = Category::first();
        $article = Article::create([
            'title' => 'Investigative Report On Healthcare Spending',
            'slug' => 'investigative-report-on-healthcare-spending',
            'summary' => 'Audit exposes key gaps in regional medical supply distribution.',
            'content' => '<p>Detailed review of supplies and procurement.</p>',
            'category_id' => $category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'in_review',
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.articles.publish', $article));

        $response->assertRedirect(route('admin.dashboard'));
        $article->refresh();
        $this->assertEquals('published', $article->status);
        $this->assertNotNull($article->published_at);
    }
}
