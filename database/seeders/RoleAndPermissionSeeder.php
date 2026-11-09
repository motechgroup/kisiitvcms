<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Articles
            ['slug' => 'articles.view', 'name' => 'View Articles', 'group' => 'Articles'],
            ['slug' => 'articles.create', 'name' => 'Create Articles', 'group' => 'Articles'],
            ['slug' => 'articles.edit_own', 'name' => 'Edit Own Articles', 'group' => 'Articles'],
            ['slug' => 'articles.edit_any', 'name' => 'Edit Any Article', 'group' => 'Articles'],
            ['slug' => 'articles.delete', 'name' => 'Delete Articles', 'group' => 'Articles'],
            ['slug' => 'articles.submit', 'name' => 'Submit for Review', 'group' => 'Articles'],
            ['slug' => 'articles.review', 'name' => 'Review Submissions', 'group' => 'Articles'],
            ['slug' => 'articles.approve', 'name' => 'Approve Articles', 'group' => 'Articles'],
            ['slug' => 'articles.publish', 'name' => 'Publish Articles', 'group' => 'Articles'],
            ['slug' => 'articles.unpublish', 'name' => 'Unpublish Articles', 'group' => 'Articles'],
            ['slug' => 'articles.schedule', 'name' => 'Schedule Articles', 'group' => 'Articles'],
            ['slug' => 'articles.feature', 'name' => 'Feature Stories', 'group' => 'Articles'],
            ['slug' => 'articles.set_breaking', 'name' => 'Set Breaking News', 'group' => 'Articles'],

            // Categories
            ['slug' => 'categories.manage', 'name' => 'Manage Categories', 'group' => 'Categories'],

            // Users & Access
            ['slug' => 'users.view', 'name' => 'View Users', 'group' => 'Users'],
            ['slug' => 'users.create', 'name' => 'Create Users', 'group' => 'Users'],
            ['slug' => 'users.edit', 'name' => 'Edit Users', 'group' => 'Users'],
            ['slug' => 'users.delete', 'name' => 'Delete Users', 'group' => 'Users'],
            ['slug' => 'roles.manage', 'name' => 'Manage Roles & Permissions', 'group' => 'Users'],

            // Media
            ['slug' => 'media.upload', 'name' => 'Upload Media', 'group' => 'Media'],
            ['slug' => 'media.manage', 'name' => 'Manage Media Library', 'group' => 'Media'],
            ['slug' => 'media.delete', 'name' => 'Delete Media', 'group' => 'Media'],

            // Editorial Modules
            ['slug' => 'homepage.manage', 'name' => 'Manage Homepage Layout', 'group' => 'Editorial'],
            ['slug' => 'breaking_news.manage', 'name' => 'Manage Breaking News Ticker', 'group' => 'Editorial'],
            ['slug' => 'live_blogs.manage', 'name' => 'Manage Live Coverage', 'group' => 'Editorial'],

            // Monetization & Ads
            ['slug' => 'advertisements.manage', 'name' => 'Manage Advertisements & Campaigns', 'group' => 'Monetization'],

            // Comments
            ['slug' => 'comments.moderate', 'name' => 'Moderate Comments', 'group' => 'Moderation'],

            // Analytics & Settings
            ['slug' => 'analytics.view', 'name' => 'View Newsroom Analytics', 'group' => 'Analytics'],
            ['slug' => 'settings.manage', 'name' => 'Manage Website Settings', 'group' => 'Settings'],
            ['slug' => 'audit_logs.view', 'name' => 'View Audit Logs', 'group' => 'Settings'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'group' => $p['group']]
            );
        }

        // Roles
        $roles = [
            'super-admin' => [
                'name' => 'Super Administrator',
                'description' => 'Full administrative access and system sovereignty.',
                'is_system' => true,
                'permissions' => array_keys($permissions),
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Manage newsroom users, categories, media, content, and reports.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.create', 'articles.edit_any', 'articles.delete', 'articles.publish',
                    'categories.manage', 'users.view', 'users.create', 'users.edit',
                    'media.upload', 'media.manage', 'homepage.manage', 'breaking_news.manage',
                    'live_blogs.manage', 'advertisements.manage', 'comments.moderate', 'analytics.view', 'settings.manage', 'audit_logs.view',
                ],
            ],
            'editor-in-chief' => [
                'name' => 'Editor-in-Chief',
                'description' => 'Final editorial authority. Approves, publishes, schedules, and oversees newsroom.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.create', 'articles.edit_any', 'articles.delete', 'articles.submit',
                    'articles.review', 'articles.approve', 'articles.publish', 'articles.unpublish',
                    'articles.schedule', 'articles.feature', 'articles.set_breaking',
                    'categories.manage', 'media.upload', 'media.manage', 'homepage.manage',
                    'breaking_news.manage', 'live_blogs.manage', 'comments.moderate', 'analytics.view',
                ],
            ],
            'managing-editor' => [
                'name' => 'Managing Editor',
                'description' => 'Coordinates newsroom operations, reviews submissions, and coordinates publishing.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.create', 'articles.edit_any', 'articles.submit', 'articles.review',
                    'articles.approve', 'articles.publish', 'articles.schedule', 'articles.feature',
                    'media.upload', 'media.manage', 'homepage.manage', 'breaking_news.manage', 'live_blogs.manage', 'analytics.view',
                ],
            ],
            'section-editor' => [
                'name' => 'Section Editor',
                'description' => 'Oversees specific assigned sections, reviews reporter drafts, and manages section stories.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.create', 'articles.edit_any', 'articles.submit',
                    'articles.review', 'articles.approve', 'articles.feature', 'media.upload', 'media.manage',
                ],
            ],
            'reporter' => [
                'name' => 'Reporter / Journalist',
                'description' => 'Drafts stories, uploads media assets, and submits content for editorial review.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.create', 'articles.edit_own', 'articles.submit', 'media.upload',
                ],
            ],
            'copy-editor' => [
                'name' => 'Copy Editor',
                'description' => 'Reviews submitted drafts, improves readability, and adds editorial corrections.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'articles.edit_any', 'articles.review',
                ],
            ],
            'photographer' => [
                'name' => 'Photographer / Video Journalist',
                'description' => 'Uploads photographs, video footage, and manages media credits.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'media.upload', 'media.manage',
                ],
            ],
            'social-media-manager' => [
                'name' => 'Social Media Manager',
                'description' => 'Curates social snippets, distributes published stories, and tracks performance.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'analytics.view',
                ],
            ],
            'advertisement-manager' => [
                'name' => 'Advertisement Manager',
                'description' => 'Manages ad campaigns, creatives, placements, and commercial revenue reports.',
                'is_system' => true,
                'permissions' => [
                    'advertisements.manage', 'analytics.view',
                ],
            ],
            'moderator' => [
                'name' => 'Moderator',
                'description' => 'Reviews and moderates public comments and reader discussions.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'comments.moderate',
                ],
            ],
            'analyst' => [
                'name' => 'Analyst / Read-only User',
                'description' => 'Accesses editorial metrics, readership trends, and analytics reports.',
                'is_system' => true,
                'permissions' => [
                    'articles.view', 'analytics.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => $data['is_system'],
                ]
            );

            $permissionIds = collect($data['permissions'])
                ->map(fn ($pSlug) => $permissionModels[$pSlug]->id ?? null)
                ->filter()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
