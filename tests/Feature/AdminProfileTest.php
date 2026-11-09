<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            SiteSettingSeeder::class,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Vincent Onyango',
            'username' => 'vincent_onyango',
            'email' => 'vincent@kisiitv.com',
            'job_title' => 'Senior Editor',
            'is_active' => true,
        ]);

        $role = Role::where('slug', 'super-admin')->first();
        if ($role) {
            $this->user->roles()->attach($role->id);
        }
    }

    public function test_authenticated_user_can_view_profile_settings(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Profile Settings &amp; Information', false);
        $response->assertSee('vincent_onyango');
        $response->assertSee('vincent@kisiitv.com');
    }

    public function test_authenticated_user_can_update_profile_information(): void
    {
        $response = $this->actingAs($this->user)->put(route('admin.profile.update'), [
            'name' => 'Vincent O. Mageto',
            'username' => 'vincent_mageto',
            'email' => 'vmageto@kisiitv.com',
            'job_title' => 'Chief Managing Editor',
            'phone' => '+254712345678',
            'bio' => 'Award-winning investigative reporter covering South Nyanza politics.',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('Vincent O. Mageto', $this->user->name);
        $this->assertEquals('vincent_mageto', $this->user->username);
        $this->assertEquals('vmageto@kisiitv.com', $this->user->email);
        $this->assertEquals('Chief Managing Editor', $this->user->job_title);
        $this->assertEquals('+254712345678', $this->user->phone);
    }

    public function test_authenticated_user_can_update_avatar_photo(): void
    {
        $fakeAvatar = UploadedFile::fake()->image('my_avatar.jpg', 400, 400);

        $response = $this->actingAs($this->user)->put(route('admin.profile.update'), [
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'avatar' => $fakeAvatar,
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $this->user->refresh();

        $this->assertNotNull($this->user->avatar_path);
        $this->assertStringContainsString('/uploads/avatars/', $this->user->avatar_path);

        // Clean up uploaded test file
        $uploadedPath = public_path(ltrim($this->user->avatar_path, '/'));
        if (File::exists($uploadedPath)) {
            File::delete($uploadedPath);
        }
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('admin.profile.update'), [
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $this->user->refresh();

        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->user->password));
    }

    public function test_header_displays_username_instead_of_full_name(): void
    {
        $response = $this->actingAs($this->user)->get(route('home'));

        $response->assertStatus(200);
        // Should see @vincent_onyango
        $response->assertSee('@vincent_onyango');
        // Should see link to profile settings
        $response->assertSee(route('admin.profile.edit'));
    }
}
