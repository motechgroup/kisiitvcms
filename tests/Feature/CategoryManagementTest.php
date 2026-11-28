<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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
            'email' => 'admin@kisiitv.com',
            'name' => 'Newsroom Director',
            'is_active' => true,
        ]);

        $role = Role::where('slug', 'super-admin')->first();
        if ($role) {
            $this->superAdmin->roles()->attach($role->id);
        }
    }

    public function test_admin_can_bulk_save_navigation_and_category_changes(): void
    {
        $categories = Category::roots()->orderBy('display_order')->take(2)->get();
        $first = $categories[0];
        $second = $categories[1];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.categories.bulk-save'), [
            'items' => [
                $first->id => [
                    'name' => 'Gusii Top Desk',
                    'display_order' => 5,
                    'show_in_menu' => '0',
                    'is_active' => '1',
                ],
                $second->id => [
                    'name' => 'County Politics Live',
                    'display_order' => 1,
                    'show_in_menu' => '1',
                    'is_active' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $first->refresh();
        $second->refresh();

        $this->assertSame('Gusii Top Desk', $first->name);
        $this->assertSame(5, $first->display_order);
        $this->assertFalse($first->show_in_menu);
        $this->assertTrue($first->is_active);

        $this->assertSame('County Politics Live', $second->name);
        $this->assertSame(1, $second->display_order);
        $this->assertTrue($second->show_in_menu);
        $this->assertTrue($second->is_active);

        // Cached navigation categories immediately reflect the saved changes
        $navNames = Category::cachedNavCategories()->pluck('name')->all();
        $this->assertContains('County Politics Live', $navNames);
        $this->assertNotContains('Gusii Top Desk', $navNames);
    }

    public function test_admin_can_edit_single_category_including_slug_and_empty_parent(): void
    {
        $category = Category::roots()->firstOrFail();

        $response = $this->actingAs($this->superAdmin)->put(route('admin.categories.update', $category), [
            'name' => 'Special Investigations',
            'slug' => 'special-investigations',
            'parent_id' => '',
            'display_order' => 2,
            'description' => 'In-depth county investigative reporting.',
            'show_in_menu' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('Special Investigations', $category->name);
        $this->assertSame('special-investigations', $category->slug);
        $this->assertNull($category->parent_id);
        $this->assertSame(2, $category->display_order);
        $this->assertTrue($category->show_in_menu);
        $this->assertTrue($category->is_active);
    }
}
