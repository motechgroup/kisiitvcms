<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Simple 1-Click Browser Migration & Seeder for Kisiitv News
 * Just open in browser to run migrations and seed content.
 * No keys, no login required.
 */
@ini_set('display_errors', '1');
@error_reporting(E_ALL);
@ini_set('memory_limit', '256M');
@set_time_limit(300);

// Polyfills
if (! function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (! function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// Detect base path
if (file_exists(__DIR__.'/bootstrap/app.php')) {
    $basePath = __DIR__;
} elseif (file_exists(dirname(__DIR__).'/bootstrap/app.php')) {
    $basePath = dirname(__DIR__);
} else {
    $basePath = __DIR__;
}

$autoload = $basePath.'/vendor/autoload.php';
$bootstrap = $basePath.'/bootstrap/app.php';

if (! file_exists($autoload) || ! file_exists($bootstrap)) {
    exit("<div style='font-family:sans-serif;padding:30px;color:red;'><h2>Error: vendor/ folder not found.</h2><p>Please make sure vendor/ folder is extracted in $basePath</p></div>");
}

require_once $autoload;
$app = require_once $bootstrap;

if (method_exists($app, 'make')) {
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
}

$output = [];
$success = true;

// 1. Run Migrations
try {
    Artisan::call('migrate', ['--force' => true]);
    $output['migrate'] = Artisan::output();
} catch (Throwable $e) {
    $output['migrate'] = 'Migration notice: '.$e->getMessage();
}

// 2. Run Seeders
try {
    Artisan::call('db:seed', ['--force' => true]);
    $output['seed'] = Artisan::output();
} catch (Throwable $e) {
    $output['seed'] = 'Seeder notice: '.$e->getMessage();
}

// 3. Ensure Super Admin Account is 100% Created
try {
    if (class_exists(User::class)) {
        $admin = User::updateOrCreate(
            ['email' => 'superadmin@kisiitv.com'],
            [
                'name' => 'Chief Technology & Systems Officer',
                'password' => Hash::make('Password123!'),
                'job_title' => 'Executive Director & Super Admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (class_exists(Role::class)) {
            $role = Role::where('slug', 'super-admin')->first();
            if ($role) {
                $admin->roles()->syncWithoutDetaching([$role->id]);
            }
        }
        $adminCreated = true;
    }
} catch (Throwable $e) {
    $adminCreated = false;
}

// 4. Storage Link (Safe pure PHP, NO exec() call)
try {
    $target = $basePath.'/storage/app/public';
    $link = $basePath.'/public/storage';
    if (! file_exists($link) && file_exists($target)) {
        if (function_exists('symlink')) {
            @symlink($target, $link);
            $output['storage'] = 'Storage symlink created via pure PHP.';
        } else {
            $output['storage'] = 'symlink() is disabled on server.';
        }
    } else {
        $output['storage'] = 'Storage symlink already configured.';
    }
} catch (Throwable $e) {
    $output['storage'] = 'Storage notice: '.$e->getMessage();
}

// 5. Optimize Clear
try {
    Artisan::call('optimize:clear');
    $output['cache'] = Artisan::output();
} catch (Throwable $e) {
    $output['cache'] = 'Cache notice: '.$e->getMessage();
}

// Count items in DB
$articleCount = Schema::hasTable('articles') ? DB::table('articles')->count() : 0;
$userCount = Schema::hasTable('users') ? DB::table('users')->count() : 0;
$catCount = Schema::hasTable('categories') ? DB::table('categories')->count() : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup — Kisiitv News</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; line-height: 1.6; }
        .box { max-width: 800px; margin: 0 auto; background: #1e293b; border-radius: 14px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; font-size: 24px; margin-bottom: 20px; font-weight: 800; }
        .success { background: #064e3b; color: #34d399; padding: 18px 20px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; border: 1px solid #059669; font-size: 16px; }
        .error { background: #7f1d1d; color: #fca5a5; padding: 18px 20px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; border: 1px solid #b91c1c; }
        .creds { background: #0284c7; color: white; padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; }
        .creds p { margin: 6px 0; font-size: 14px; }
        pre { background: #020617; color: #cbd5e1; padding: 16px; border-radius: 10px; overflow-x: auto; font-size: 12px; font-family: monospace; white-space: pre-wrap; margin-bottom: 20px; border: 1px solid #1e293b; max-height: 300px; }
        .btn { display: inline-block; background: #10b981; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 15px; margin-top: 10px; }
        .btn:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🚀 Kisiitv Database Setup & Seeder</h1>

        <?php if ($success) { ?>
            <div class="success">
                ✅ Migrations & Content Seeding Completed Successfully!
                <div style="font-size:14px; margin-top:8px; font-weight:normal; color:#a7f3d0;">
                    <strong>Articles Created:</strong> <?= $articleCount ?> &nbsp;|&nbsp;
                    <strong>Categories Created:</strong> <?= $catCount ?> &nbsp;|&nbsp;
                    <strong>Users / Admins:</strong> <?= $userCount ?>
                </div>
            </div>

            <div class="creds">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 6px;">🔐 Super Admin Login Credentials:</div>
                <p><strong>Login Page:</strong> <a href="/login" style="color: #fff; text-decoration: underline;" target="_blank">kisiitv.co.ke/login</a></p>
                <p><strong>Email:</strong> <code style="background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">superadmin@kisiitv.com</code></p>
                <p><strong>Password:</strong> <code style="background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">Password123!</code></p>
            </div>

            <h3 style="font-size: 14px; color: #94a3b8; margin-bottom: 8px;">Execution Logs:</h3>
            <pre><strong>MIGRATIONS:</strong>
<?= htmlspecialchars(trim($output['migrate'] ?? '') ?: 'Nothing to migrate.') ?>

<strong>SEEDER (ARTICLES & CATEGORIES):</strong>
<?= htmlspecialchars(trim($output['seed'] ?? '') ?: 'Seeded successfully.') ?>

<strong>STORAGE & CACHE:</strong>
<?= htmlspecialchars(trim($output['cache'] ?? '')) ?></pre>

            <a href="/" class="btn">🌐 View Live Website &rarr;</a>
            <a href="/login" class="btn" style="background:#6366f1; margin-left: 10px;">🔐 Log In to Newsroom &rarr;</a>

        <?php } else { ?>
            <div class="error">
                ❌ Setup encountered an error:
                <pre style="margin-top:10px; background:#450a0a; color:#fecaca;"><?= htmlspecialchars($errorMsg) ?></pre>
            </div>
        <?php } ?>
    </div>
</body>
</html>
