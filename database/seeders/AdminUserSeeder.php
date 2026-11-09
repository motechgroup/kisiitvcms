<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCategoryAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Administrator
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@kisiitv.com'],
            [
                'name' => 'Chief Technology & Systems Officer',
                'password' => Hash::make('Password123!'),
                'job_title' => 'Executive Director & Super Admin',
                'bio' => 'Lead systems administrator and newsroom platform architect.',
                'phone' => '+254700000001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // 2. Editor-in-Chief
        $editorInChief = User::updateOrCreate(
            ['email' => 'editor@kisiitv.com'],
            [
                'name' => 'Sarah Moraa',
                'password' => Hash::make('Password123!'),
                'job_title' => 'Editor-in-Chief',
                'bio' => 'Veteran journalist overseeing editorial integrity and publishing policies.',
                'phone' => '+254700000002',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $eicRole = Role::where('slug', 'editor-in-chief')->first();
        if ($eicRole) {
            $editorInChief->roles()->syncWithoutDetaching([$eicRole->id]);
        }

        // 3. Politics Section Editor
        $politicsEditor = User::updateOrCreate(
            ['email' => 'politics.editor@kisiitv.com'],
            [
                'name' => 'David Ochieng',
                'password' => Hash::make('Password123!'),
                'job_title' => 'Senior Politics Editor',
                'bio' => 'Investigative analyst covering the National Assembly and executive affairs.',
                'phone' => '+254700000003',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $sectionEditorRole = Role::where('slug', 'section-editor')->first();
        if ($sectionEditorRole) {
            $politicsEditor->roles()->syncWithoutDetaching([$sectionEditorRole->id]);
        }
        $politicsCategory = Category::where('slug', 'politics')->first();
        if ($politicsCategory) {
            UserCategoryAccess::updateOrCreate(
                ['user_id' => $politicsEditor->id, 'category_id' => $politicsCategory->id],
                ['can_approve' => true, 'can_publish' => true]
            );
        }

        // 4. Field Reporter
        $reporter = User::updateOrCreate(
            ['email' => 'reporter@kisiitv.com'],
            [
                'name' => 'Faith Nyaboke',
                'password' => Hash::make('Password123!'),
                'job_title' => 'Senior Investigative Reporter',
                'bio' => 'Field correspondent reporting on crime, devolution, and community stories.',
                'phone' => '+254700000004',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $reporterRole = Role::where('slug', 'reporter')->first();
        if ($reporterRole) {
            $reporter->roles()->syncWithoutDetaching([$reporterRole->id]);
        }
    }
}
