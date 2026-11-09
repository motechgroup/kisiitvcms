<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\NewsContentSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryNavigationTest extends TestCase
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

    public function test_guest_is_redirected_from_admin_categories(): void
    {
        $response = $this->get(route('admin.categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_category_navigation_manager(): void
    {
        $user = User::where('email', 'superadmin@kisiitv.com')->first();

        $response = $this->actingAs($user)->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Editorial Navigation &amp; Categories', false);
        $response->assertSee('Active Navigation Menu Items');
        $response->assertSee('Kenya');
        $response->assertSee('Politics');
    }

    public function test_admin_can_create_new_category_for_navigation(): void
    {
        $user = User::where('email', 'superadmin@kisiitv.com')->first();

        $response = $this->actingAs($user)->post(route('admin.categories.store'), [
            'name' => 'Special Investigations',
            'slug' => 'special-investigations',
            'display_order' => 15,
            'show_in_menu' => 1,
            'description' => 'In-depth investigative files and undercover reporting',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Special Investigations',
            'slug' => 'special-investigations',
            'show_in_menu' => true,
        ]);

        // Verify it appears in the public navigation / More sections
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Special Investigations');
    }

    public function test_admin_can_toggle_category_menu_status(): void
    {
        $user = User::where('email', 'superadmin@kisiitv.com')->first();
        $category = Category::where('slug', 'kenya')->first();
        $this->assertTrue((bool) $category->show_in_menu);

        // Toggle OFF
        $response = $this->actingAs($user)->patch(route('admin.categories.toggle-menu', $category));
        $response->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertFalse((bool) $category->show_in_menu);

        // Toggle ON
        $this->actingAs($user)->patch(route('admin.categories.toggle-menu', $category));
        $category->refresh();
        $this->assertTrue((bool) $category->show_in_menu);
    }
}
