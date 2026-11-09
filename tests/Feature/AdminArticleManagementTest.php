<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Category $category;

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
            'name' => 'Lead Editor',
            'is_active' => true,
        ]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $this->superAdmin->roles()->attach($superAdminRole->id);
        }

        $this->category = Category::firstOrFail();
    }

    public function test_super_admin_can_view_articles_index_with_filters(): void
    {
        Article::create([
            'title' => 'Breaking Political Summit in Kisii',
            'slug' => 'breaking-political-summit-in-kisii',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'published',
            'is_breaking' => true,
            'summary' => 'Breaking summit summary',
            'content' => '<p>Political leaders gather for conference.</p>',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.articles.index', [
            'search' => 'Summit',
            'status' => 'published',
            'flag' => 'breaking',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Breaking Political Summit in Kisii');
        $response->assertSee('⚡ Breaking');
    }

    public function test_super_admin_can_create_article_with_image_upload(): void
    {
        $fakeImage = UploadedFile::fake()->image('investigation.jpg', 1200, 800);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.articles.store'), [
            'title' => 'Modern Healthcare Center Opened in Ogembo',
            'subtitle' => 'Serving over 50,000 residents across Kisii County',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'published',
            'article_type' => 'investigation',
            'summary' => 'New medical equipment and maternal health ward unveiled.',
            'content' => '<p>The facility will provide 24-hour emergency triage and specialized surgery units for local families.</p>',
            'featured_image_file' => $fakeImage,
            'image_caption' => 'Exterior view of the newly commissioned Ogembo medical wing.',
            'is_featured' => 1,
            'is_breaking' => 0,
            'is_editors_pick' => 1,
            'meta_title' => 'Healthcare Expansion in Ogembo - Kisii TV Special',
            'meta_description' => 'Comprehensive look at the new medical wing opened in Kisii.',
            'tags' => 'Healthcare, Ogembo, Kisii County, Devolution',
        ]);

        $response->assertRedirect(route('admin.articles.index'));
        $response->assertSessionHas('success');

        $article = Article::where('slug', 'modern-healthcare-center-opened-in-ogembo')->first();
        $this->assertNotNull($article);
        $this->assertEquals('published', $article->status);
        $this->assertEquals('investigation', $article->article_type);
        $this->assertTrue($article->is_featured);
        $this->assertTrue($article->is_editors_pick);
        $this->assertStringContainsString('/uploads/articles/', $article->featured_image);
        $this->assertGreaterThan(0, $article->reading_time_minutes);

        // Verify tags synced
        $this->assertCount(4, $article->tags);

        // Clean up test upload file if written
        $relativeImagePath = parse_url($article->featured_image, PHP_URL_PATH);
        $uploadedPath = public_path(ltrim($relativeImagePath, '/'));
        if (File::exists($uploadedPath)) {
            File::delete($uploadedPath);
        }
    }

    public function test_super_admin_can_clone_article(): void
    {
        $original = Article::create([
            'title' => 'Original Story to Duplicate',
            'slug' => 'original-story-to-duplicate',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'published',
            'summary' => 'Original summary description',
            'content' => '<p>Original long form paragraph content.</p>',
            'views_count' => 1250,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.articles.clone', $original));

        $cloned = Article::where('title', 'Original Story to Duplicate (Copy)')->first();
        $this->assertNotNull($cloned);
        $this->assertEquals('draft', $cloned->status);
        $this->assertEquals(0, $cloned->views_count);
        $this->assertNull($cloned->published_at);
        $this->assertEquals($original->category_id, $cloned->category_id);

        $response->assertRedirect(route('admin.articles.edit', $cloned));
    }

    public function test_super_admin_can_quick_toggle_flags_and_status(): void
    {
        $article = Article::create([
            'title' => 'Toggle Test Story Headline',
            'slug' => 'toggle-test-story-headline',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'draft',
            'is_breaking' => false,
            'is_featured' => false,
            'is_editors_pick' => false,
            'summary' => 'Toggle summary',
            'content' => '<p>Content</p>',
        ]);

        // Toggle Breaking
        $this->actingAs($this->superAdmin)->patch(route('admin.articles.toggle-breaking', $article));
        $this->assertTrue($article->fresh()->is_breaking);

        // Toggle Featured
        $this->actingAs($this->superAdmin)->patch(route('admin.articles.toggle-featured', $article));
        $this->assertTrue($article->fresh()->is_featured);

        // Toggle Editor's Pick
        $this->actingAs($this->superAdmin)->patch(route('admin.articles.toggle-editors-pick', $article));
        $this->assertTrue($article->fresh()->is_editors_pick);

        // Toggle Status to Published
        $this->actingAs($this->superAdmin)->patch(route('admin.articles.toggle-status', $article));
        $this->assertEquals('published', $article->fresh()->status);
        $this->assertNotNull($article->fresh()->published_at);

        // Toggle Status back to Draft
        $this->actingAs($this->superAdmin)->patch(route('admin.articles.toggle-status', $article));
        $this->assertEquals('draft', $article->fresh()->status);
    }

    public function test_super_admin_can_perform_bulk_actions(): void
    {
        $a1 = Article::create([
            'title' => 'Bulk Item 1',
            'slug' => 'bulk-item-1',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'draft',
            'summary' => 'Summary 1',
            'content' => '<p>Content 1</p>',
        ]);

        $a2 = Article::create([
            'title' => 'Bulk Item 2',
            'slug' => 'bulk-item-2',
            'category_id' => $this->category->id,
            'user_id' => $this->superAdmin->id,
            'status' => 'draft',
            'summary' => 'Summary 2',
            'content' => '<p>Content 2</p>',
        ]);

        // Bulk Publish
        $response = $this->actingAs($this->superAdmin)->post(route('admin.articles.bulk'), [
            'action' => 'publish',
            'selected_articles' => [$a1->id, $a2->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals('published', $a1->fresh()->status);
        $this->assertEquals('published', $a2->fresh()->status);

        // Bulk Feature
        $this->actingAs($this->superAdmin)->post(route('admin.articles.bulk'), [
            'action' => 'feature',
            'selected_articles' => [$a1->id, $a2->id],
        ]);

        $this->assertTrue($a1->fresh()->is_featured);
        $this->assertTrue($a2->fresh()->is_featured);

        // Bulk Delete
        $this->actingAs($this->superAdmin)->post(route('admin.articles.bulk'), [
            'action' => 'delete',
            'selected_articles' => [$a1->id],
        ]);

        $this->assertDatabaseMissing('articles', ['id' => $a1->id]);
        $this->assertDatabaseHas('articles', ['id' => $a2->id]);
    }
}
