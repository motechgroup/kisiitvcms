<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
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

    // 1. SITE SETTINGS & BRANDING TESTS
    public function test_super_admin_can_view_settings_panel(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Platform Branding & Configuration Engine');
        $response->assertSee('Theme Styling & Dynamic Color Palette');
        $response->assertSee('Site Footer & Newsroom Contact Details');
    }

    public function test_super_admin_can_update_site_settings_and_colors(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.update'), [
            'site_name' => 'Kisii TV Prime Network',
            'site_tagline' => 'The Voice of South Nyanza & Kenya',
            'site_description' => 'Updated editorial meta description',
            'primary_color' => '#123456',
            'accent_color' => '#ff0000',
            'header_bg_color' => '#001122',
            'ticker_bg_color' => '#cc0000',
            'contact_email' => 'editor@kisiitvprime.com',
            'contact_phone' => '+254711223344',
            'footer_copyright' => 'Kisii TV Prime Network. All rights reserved.',
            'breaking_ticker_enabled' => 1,
            'enable_comments' => 1,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Kisii TV Prime Network', SiteSetting::get('site_name'));
        $this->assertEquals('#123456', SiteSetting::get('primary_color'));
        $this->assertEquals('#ff0000', SiteSetting::get('accent_color'));
        $this->assertEquals('editor@kisiitvprime.com', SiteSetting::get('contact_email'));

        // Verify public site layout reflects updated branding
        $publicResponse = $this->get(route('home'));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Kisii TV Prime Network');
    }

    // 2. ARTICLE MANAGEMENT TESTS
    public function test_super_admin_can_view_articles_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.articles.index'));

        $response->assertStatus(200);
        $response->assertSee('Newsroom Editorial Content');
    }

    public function test_super_admin_can_create_article_via_full_form(): void
    {
        $category = Category::first();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.articles.store'), [
            'title' => 'Major Highway Expansion Commissioned in Kisii County',
            'subtitle' => 'Infrastructure boost for Lake Region connectivity',
            'category_id' => $category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'published',
            'summary' => 'President inaugurates 80km dual carriageway highway.',
            'content' => '<p>The infrastructure project links Kisii town with regional trading centers.</p>',
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.articles.index'));
        $this->assertDatabaseHas('articles', [
            'title' => 'Major Highway Expansion Commissioned in Kisii County',
            'status' => 'published',
            'is_featured' => true,
        ]);
    }

    public function test_super_admin_can_update_article(): void
    {
        $category = Category::first();
        $article = Article::create([
            'title' => 'Initial Story Title',
            'slug' => 'initial-story-title',
            'category_id' => $category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'draft',
            'summary' => 'Initial summary',
            'content' => '<p>Initial content</p>',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.articles.update', $article), [
            'title' => 'Updated Story Title Revised',
            'category_id' => $category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'published',
            'summary' => 'Updated summary text',
            'content' => '<p>Updated content text</p>',
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.articles.index'));
        $article->refresh();
        $this->assertEquals('Updated Story Title Revised', $article->title);
        $this->assertEquals('published', $article->status);
    }

    public function test_super_admin_can_delete_article(): void
    {
        $category = Category::first();
        $article = Article::create([
            'title' => 'Article to be Deleted',
            'slug' => 'article-to-be-deleted',
            'category_id' => $category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'draft',
            'summary' => 'Delete summary',
            'content' => '<p>Delete content</p>',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.articles.destroy', $article));

        $response->assertRedirect(route('admin.articles.index'));
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    // 3. USER MANAGEMENT TESTS
    public function test_super_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Editorial Staff & System Access');
        $response->assertSee('Lead Architect');
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $reporterRole = Role::where('slug', 'editor-in-chief')->firstOrFail();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name' => 'Vincent Onyango',
            'email' => 'vincent.onyango@kisiitv.com',
            'password' => 'Password123!',
            'job_title' => 'Senior Political Bureau Chief',
            'phone' => '+254701234567',
            'is_active' => 1,
            'role_ids' => [$reporterRole->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'vincent.onyango@kisiitv.com',
            'job_title' => 'Senior Political Bureau Chief',
        ]);

        $createdUser = User::where('email', 'vincent.onyango@kisiitv.com')->first();
        $this->assertTrue($createdUser->roles->contains($reporterRole));
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.users.destroy', $this->superAdmin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }
}
