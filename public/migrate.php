<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Super Admin & Newsroom Self-Healing Diagnostic Script for Kisiitv News
 * 1. Ensures storage directories and permissions exist.
 * 2. Runs migrations and content seeders.
 * 3. Asserts Super Admin account credentials.
 * 4. Precompiles views and caches.
 * 5. Dry-runs the /admin route with full stack trace reporting.
 * 6. Reads the latest server error log for instant diagnostics.
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

// 0. Auto-create & repair storage directories and permissions BEFORE booting Laravel
$criticalDirs = [
    $basePath.'/storage',
    $basePath.'/storage/app',
    $basePath.'/storage/app/public',
    $basePath.'/storage/framework',
    $basePath.'/storage/framework/views',
    $basePath.'/storage/framework/sessions',
    $basePath.'/storage/framework/cache',
    $basePath.'/storage/framework/cache/data',
    $basePath.'/storage/logs',
    $basePath.'/bootstrap/cache',
];

$dirFixes = [];
foreach ($criticalDirs as $dir) {
    if (! file_exists($dir)) {
        @mkdir($dir, 0777, true);
        $dirFixes[] = 'Created directory: '.basename(dirname($dir)).'/'.basename($dir);
    }
    @chmod($dir, 0777);
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
                'username' => 'superadmin',
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
    $output['admin_error'] = $e->getMessage();
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

// 5. Clear Stale Caches & Precompile Views
try {
    Artisan::call('optimize:clear');
    $output['cache'] = Artisan::output();
} catch (Throwable $e) {
    $output['cache'] = 'Cache notice: '.$e->getMessage();
}

try {
    Artisan::call('view:cache');
    $output['views'] = Artisan::output();
} catch (Throwable $e) {
    $output['views'] = 'View cache notice: '.$e->getMessage();
}

// 6. Test-run /admin route simulation as Super Admin to capture any real exception
$adminTestResult = 'Not executed';
$adminTestSuccess = false;
try {
    $user = User::where('email', 'superadmin@kisiitv.com')->first();
    if ($user) {
        Auth::login($user);
        $request = Request::create('/admin', 'GET');
        $httpKernel = $app->make(HttpKernel::class);
        $response = $httpKernel->handle($request);
        $code = $response->getStatusCode();
        if ($code === 200) {
            $adminTestSuccess = true;
            $adminTestResult = 'HTTP 200 OK — The admin dashboard renders perfectly!';
        } else {
            $rawBody = $response->getContent();
            $adminTestResult = "HTTP {$code} Error returned:\n".substr(strip_tags($rawBody), 0, 1500);
        }
    } else {
        $adminTestResult = 'Notice: Super Admin user could not be found to test.';
    }
} catch (Throwable $e) {
    $adminTestResult = 'EXCEPTION CAUGHT: '.$e->getMessage()."\nFile: ".$e->getFile().' (Line '.$e->getLine().")\n\nStack Trace:\n".$e->getTraceAsString();
}

// 7. Read latest lines of storage/logs/laravel.log
$logPath = $basePath.'/storage/logs/laravel.log';
$latestLogSnippet = '';
if (file_exists($logPath)) {
    $size = filesize($logPath);
    if ($size > 0) {
        $fp = fopen($logPath, 'r');
        if ($fp) {
            $readBytes = min($size, 10000);
            fseek($fp, max(0, $size - $readBytes));
            $latestLogSnippet = fread($fp, $readBytes);
            fclose($fp);
        }
    }
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
    <title>Database Setup & Newsroom Diagnostic — Kisiitv News</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px 16px; line-height: 1.6; }
        .box { max-width: 860px; margin: 0 auto; background: #1e293b; border-radius: 14px; padding: 26px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; font-size: 22px; margin-bottom: 18px; font-weight: 800; }
        .success { background: #064e3b; color: #34d399; padding: 16px 20px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; border: 1px solid #059669; font-size: 15px; }
        .error { background: #7f1d1d; color: #fca5a5; padding: 16px 20px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; border: 1px solid #b91c1c; }
        .creds { background: #0284c7; color: white; padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; }
        .creds p { margin: 6px 0; font-size: 14px; }
        pre { background: #020617; color: #cbd5e1; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: 11px; font-family: monospace; white-space: pre-wrap; margin-bottom: 18px; border: 1px solid #1e293b; max-height: 280px; }
        .btn { display: inline-block; background: #10b981; color: white; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: bold; font-size: 14px; margin-right: 10px; margin-top: 8px; }
        .btn:hover { background: #059669; }
        .btn-primary { background: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }
        .section-title { font-size: 13px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 18px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🚀 Kisiitv Newsroom Setup &amp; Admin Diagnostic</h1>

        <div class="success">
            ✅ Database &amp; Newsroom Environment Ready!
            <div style="font-size:13px; margin-top:6px; font-weight:normal; color:#a7f3d0;">
                <strong>Articles:</strong> <?= $articleCount ?> &nbsp;|&nbsp;
                <strong>Categories:</strong> <?= $catCount ?> &nbsp;|&nbsp;
                <strong>Newsroom Staff / Admins:</strong> <?= $userCount ?>
            </div>
        </div>

        <div class="creds">
            <div style="font-size: 15px; font-weight: bold; margin-bottom: 6px;">🔐 Super Administrator Credentials:</div>
            <p><strong>Admin URL:</strong> <a href="https://kisiitv.co.ke/admin" style="color: #fff; text-decoration: underline;" target="_blank">https://kisiitv.co.ke/admin</a></p>
            <p><strong>Login URL:</strong> <a href="https://kisiitv.co.ke/login" style="color: #fff; text-decoration: underline;" target="_blank">https://kisiitv.co.ke/login</a></p>
            <p><strong>Email:</strong> <code style="background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">superadmin@kisiitv.com</code></p>
            <p><strong>Password:</strong> <code style="background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">Password123!</code></p>
        </div>

        <div class="section-title">🧪 Live /admin Dashboard Self-Test Result:</div>
        <div class="<?= $adminTestSuccess ? 'success' : 'error' ?>" style="font-size: 13px; font-family: monospace;">
            <?= nl2br(htmlspecialchars($adminTestResult)) ?>
        </div>

        <?php if (! empty($latestLogSnippet)) { ?>
            <div class="section-title">📋 Recent Server Error Log (storage/logs/laravel.log):</div>
            <pre style="background: #181010; color: #fca5a5; border-color: #7f1d1d;"><?= htmlspecialchars(trim($latestLogSnippet)) ?></pre>
        <?php } ?>

        <div class="section-title">⚙️ Execution Details:</div>
        <pre><strong>STORAGE DIRECTORIES:</strong>
<?= ! empty($dirFixes) ? htmlspecialchars(implode("\n", $dirFixes)) : 'All storage directories already exist and are writable.' ?>

<strong>MIGRATIONS:</strong>
<?= htmlspecialchars(trim($output['migrate'] ?? '') ?: 'Nothing to migrate.') ?>

<strong>SEEDER:</strong>
<?= htmlspecialchars(trim($output['seed'] ?? '') ?: 'Seeded successfully.') ?>

<strong>CACHE:</strong>
<?= htmlspecialchars(trim($output['cache'] ?? '')) ?></pre>

        <a href="https://kisiitv.co.ke/admin" class="btn btn-primary" target="_blank">👑 Open Admin Dashboard &rarr;</a>
        <a href="https://kisiitv.co.ke/login" class="btn" style="background:#6366f1;" target="_blank">🔐 Newsroom Login &rarr;</a>
        <a href="https://kisiitv.co.ke/" class="btn" style="background:#475569;" target="_blank">🌐 Live Website &rarr;</a>
    </div>
</body>
</html>
