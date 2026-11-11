<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Kisiitv News — Production Deployment & Database Manager
 *
 * Designed specifically for Shared Hosting / cPanel environments:
 * - 100% Standalone: Zero external CSS or JS dependencies (impossible to render unstyled)
 * - 1-Click Complete Setup: Runs database migrations + seeds articles, categories & admins
 * - Admin Account Manager: View default credentials or create / reset super admin on the spot
 * - Live Database Stats: Real-time counter of articles, categories, users, and tables
 *
 * @author Antigravity
 *
 * @version 3.0.0
 */

// 1. Hardening & Performance Setup
@ini_set('display_errors', '1');
@error_reporting(E_ALL);
@ini_set('memory_limit', '256M');
@set_time_limit(300);

// Polyfills for PHP 7.4 / 8.0 compatibility
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
if (! function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Anti-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

define('DEPLOYER_VERSION', '3.0.0');

// Smart BASE_PATH detection (works in public/ OR root public_html/)
if (file_exists(__DIR__.'/bootstrap/app.php')) {
    define('BASE_PATH', __DIR__);
} elseif (file_exists(dirname(__DIR__).'/bootstrap/app.php')) {
    define('BASE_PATH', dirname(__DIR__));
} else {
    define('BASE_PATH', __DIR__);
}

define('STORAGE_PATH', BASE_PATH.'/storage');

// 2. Load Environment Configuration
function loadEnv(): array
{
    $envPath = BASE_PATH.'/.env';
    $config = [
        'APP_NAME' => 'Kisiitv News',
        'APP_ENV' => 'production',
        'APP_KEY' => '',
        'DEPLOY_SECRET' => 'deployer_admin_secret_key_2026',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => '',
    ];

    if (file_exists($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim(trim($v), "\"'\t\n\r\0\x0B");
                $config[$k] = $v;
            }
        }
    }

    return $config;
}

$env = loadEnv();
$secretKey = ! empty($env['DEPLOY_SECRET']) ? $env['DEPLOY_SECRET'] : 'deployer_admin_secret_key_2026';

// 3. Laravel Console Bootstrapper
class LaravelApp
{
    protected static $booted = false;

    protected static $app = null;

    public static function boot(): bool
    {
        if (self::$booted) {
            return true;
        }

        $autoload = BASE_PATH.'/vendor/autoload.php';
        $bootstrap = BASE_PATH.'/bootstrap/app.php';

        if (! file_exists($autoload) || ! file_exists($bootstrap)) {
            return false;
        }

        try {
            if (! defined('LARAVEL_START')) {
                define('LARAVEL_START', microtime(true));
            }
            require_once $autoload;
            self::$app = require_once $bootstrap;

            if (method_exists(self::$app, 'make')) {
                $kernel = self::$app->make(Kernel::class);
                $kernel->bootstrap();
            }
            self::$booted = true;

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function artisan(string $command, array $params = []): array
    {
        $startTime = microtime(true);
        if (! self::boot()) {
            return [
                'success' => false,
                'output' => 'ERROR: Unable to bootstrap Laravel. Ensure vendor/ folder is present.',
                'duration' => 0,
            ];
        }

        try {
            $code = Artisan::call($command, $params);
            $output = Artisan::output();
            $duration = round(microtime(true) - $startTime, 3);

            return [
                'success' => ($code === 0),
                'output' => trim($output) ?: 'Command completed successfully.',
                'duration' => $duration,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'output' => 'Exception: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine(),
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }
    }

    public static function getStats(): array
    {
        $stats = [
            'db_connected' => false,
            'db_name' => '',
            'articles' => 0,
            'categories' => 0,
            'users' => 0,
            'migrations' => 0,
            'error' => '',
        ];

        if (! self::boot()) {
            $stats['error'] = 'Laravel not bootstrapped (vendor missing?)';

            return $stats;
        }

        try {
            $pdo = DB::connection()->getPdo();
            $stats['db_connected'] = true;
            $stats['db_name'] = DB::connection()->getDatabaseName();

            // Count tables safely
            if (Schema::hasTable('migrations')) {
                $stats['migrations'] = (int) DB::table('migrations')->count();
            }
            if (Schema::hasTable('articles')) {
                $stats['articles'] = (int) DB::table('articles')->count();
            }
            if (Schema::hasTable('categories')) {
                $stats['categories'] = (int) DB::table('categories')->count();
            }
            if (Schema::hasTable('users')) {
                $stats['users'] = (int) DB::table('users')->count();
            }
        } catch (Throwable $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    public static function createAdmin(string $name, string $email, string $password): array
    {
        if (! self::boot()) {
            return ['success' => false, 'message' => 'Laravel not bootstrapped.'];
        }

        try {
            $user = User::updateOrCreate(
                ['email' => trim($email)],
                [
                    'name' => trim($name),
                    'password' => Hash::make($password),
                    'job_title' => 'Executive Director & Super Admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Assign super-admin role if exists
            if (class_exists(Role::class)) {
                $role = Role::where('slug', 'super-admin')->first();
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            }

            return [
                'success' => true,
                'message' => "Super Admin account '{$email}' created / updated successfully! You can now log into the newsroom.",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error creating admin: '.$e->getMessage(),
            ];
        }
    }
}

// 4. Authentication Check
// Support logging in via URL: ?key=deployer_admin_secret_key_2026
if (isset($_GET['key']) && hash_equals($secretKey, (string) $_GET['key'])) {
    $_SESSION['kisiitv_deployer_auth'] = true;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['kisiitv_deployer_auth']);
    header('Location: '.strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_key'])) {
    if (hash_equals($secretKey, (string) $_POST['login_key'])) {
        $_SESSION['kisiitv_deployer_auth'] = true;
        header('Location: '.strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $loginError = 'Invalid deployment secret key. Please try again.';
    }
}

$isAuth = ! empty($_SESSION['kisiitv_deployer_auth']);

// 5. Handle Post Actions
$actionResult = null;

if ($isAuth && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['operation'])) {
    $op = $_POST['operation'];

    switch ($op) {
        case 'full_setup':
            // 1. Run migrations
            $r1 = LaravelApp::artisan('migrate', ['--force' => true]);
            // 2. Run seeders
            $r2 = LaravelApp::artisan('db:seed', ['--force' => true]);
            // 3. Storage link
            $r3 = LaravelApp::artisan('storage:link');
            // 4. Optimize clear
            $r4 = LaravelApp::artisan('optimize:clear');

            $allSuccess = ($r1['success'] && $r2['success']);
            $combinedOutput = "========================================\n";
            $combinedOutput .= "🚀 1. DATABASE MIGRATIONS:\n".$r1['output']."\n\n";
            $combinedOutput .= "🌱 2. DATABASE SEEDING (ARTICLES & CATEGORIES):\n".$r2['output']."\n\n";
            $combinedOutput .= "🔗 3. STORAGE SYMLINK:\n".$r3['output']."\n\n";
            $combinedOutput .= "🧹 4. CACHE OPTIMIZATION:\n".$r4['output']."\n";
            $combinedOutput .= '========================================';

            $actionResult = [
                'success' => $allSuccess,
                'title' => $allSuccess ? '✅ Full Setup Completed Successfully!' : '⚠️ Setup Completed with Warnings',
                'output' => $combinedOutput,
            ];
            break;

        case 'migrate':
            $res = LaravelApp::artisan('migrate', ['--force' => true]);
            $actionResult = [
                'success' => $res['success'],
                'title' => $res['success'] ? '✅ Migrations Executed' : '❌ Migration Failed',
                'output' => $res['output'],
            ];
            break;

        case 'seed':
            $res = LaravelApp::artisan('db:seed', ['--force' => true]);
            LaravelApp::artisan('optimize:clear');
            $actionResult = [
                'success' => $res['success'],
                'title' => $res['success'] ? '✅ Database Seeded Successfully' : '❌ Seeding Failed',
                'output' => $res['output'],
            ];
            break;

        case 'create_admin':
            $name = trim($_POST['admin_name'] ?? 'Super Admin');
            $email = trim($_POST['admin_email'] ?? 'superadmin@kisiitv.com');
            $pass = trim($_POST['admin_password'] ?? 'Password123!');

            if (empty($email) || empty($pass)) {
                $actionResult = [
                    'success' => false,
                    'title' => '❌ Error',
                    'output' => 'Email and Password are required to create an administrator account.',
                ];
            } else {
                $res = LaravelApp::createAdmin($name, $email, $pass);
                $actionResult = [
                    'success' => $res['success'],
                    'title' => $res['success'] ? '✅ Admin Account Ready' : '❌ Failed to Create Admin',
                    'output' => $res['message'],
                ];
            }
            break;

        case 'storage_link':
            $res = LaravelApp::artisan('storage:link');
            // Fallback native symlink
            if (! file_exists(BASE_PATH.'/public/storage') && file_exists(STORAGE_PATH.'/app/public')) {
                @symlink(STORAGE_PATH.'/app/public', BASE_PATH.'/public/storage');
                $res['output'] .= "\nNative PHP symlink created for public/storage.";
            }
            $actionResult = [
                'success' => $res['success'],
                'title' => '🔗 Storage Link',
                'output' => $res['output'],
            ];
            break;

        case 'cache_clear':
            $res = LaravelApp::artisan('optimize:clear');
            $actionResult = [
                'success' => $res['success'],
                'title' => '🧹 Caches Cleared',
                'output' => $res['output'],
            ];
            break;
    }
}

// 6. Direct URL Action trigger (e.g. ?run=seed&key=...)
if ($isAuth && isset($_GET['run'])) {
    if ($_GET['run'] === 'full' || $_GET['run'] === 'seed') {
        $r1 = LaravelApp::artisan('migrate', ['--force' => true]);
        $r2 = LaravelApp::artisan('db:seed', ['--force' => true]);
        LaravelApp::artisan('storage:link');
        LaravelApp::artisan('optimize:clear');
        $actionResult = [
            'success' => ($r1['success'] && $r2['success']),
            'title' => '✅ Full Setup Executed via Direct Link',
            'output' => "MIGRATIONS:\n".$r1['output']."\n\nSEEDING:\n".$r2['output'],
        ];
    }
}

$stats = $isAuth ? LaravelApp::getStats() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Control Center — Kisiitv News</title>
    <style>
        /* 100% Standalone, Bulletproof CSS (Guaranteed Styling) */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            background-color: #030712;
            color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 15px;
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0284c7, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .brand-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.01em;
        }
        .brand-text p {
            font-size: 12px;
            color: #94a3b8;
        }
        .top-links {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .btn-live {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border-color: rgba(14, 165, 233, 0.3);
        }
        .btn-live:hover {
            background: #0ea5e9;
            color: #ffffff;
        }
        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #38bdf8;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .stat-status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .status-ok { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-warn { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

        /* Main Setup Hero Card */
        .hero-card {
            background: linear-gradient(135deg, #0f172a, #1e1b4b);
            border: 1px solid #3730a3;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 20px 40px -10px rgba(55, 48, 163, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        .hero-info h2 {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hero-info p {
            font-size: 13px;
            color: #cbd5e1;
            max-width: 540px;
            line-height: 1.6;
        }
        .btn-hero {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 16px 28px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.6);
            filter: brightness(1.1);
        }

        /* Action Result Alert */
        .alert-box {
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .alert-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-output {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            color: #e2e8f0;
            max-height: 280px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        /* Two Columns Layout */
        .two-cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        .card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #1e293b;
        }
        .card-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Forms & Inputs */
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .input-text {
            width: 100%;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 10px 14px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.15s ease;
        }
        .input-text:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            text-decoration: none;
            width: 100%;
        }
        .btn-indigo {
            background: linear-gradient(135deg, #0284c7, #6366f1);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
        }
        .btn-indigo:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }
        .btn-secondary:hover {
            background: #334155;
            color: #ffffff;
        }

        /* Button Grid */
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        /* Login Card */
        .login-wrap {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 36px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }

        /* Default Creds Box */
        .creds-box {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.3);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .creds-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .creds-row strong {
            color: #38bdf8;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
    </style>
</head>
<body>

<div class="container">

<?php if (! $isAuth) { ?>
    <!-- 1. LOGIN SCREEN -->
    <div class="login-wrap">
        <div class="login-card">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
                <div class="brand-icon">🚀</div>
                <div>
                    <h1 style="font-size:20px;font-weight:800;color:#fff;">Kisiitv News</h1>
                    <p style="font-size:12px;color:#94a3b8;">Production Database & Setup Manager</p>
                </div>
            </div>

            <?php if (! empty($loginError)) { ?>
                <div style="background:rgba(239,68,68,0.15);border:1px solid #ef4444;border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#fca5a5;">
                    ⚠️ <?= htmlspecialchars($loginError) ?>
                </div>
            <?php } ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="login_key">Deployment Secret Key</label>
                    <input type="password" id="login_key" name="login_key" required autofocus class="input-text" placeholder="Enter deployment key...">
                </div>
                <button type="submit" class="btn btn-indigo" style="margin-top:12px;">
                    <span>Unlock Control Center</span>
                    <span>&rarr;</span>
                </button>
            </form>

            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #1e293b;font-size:11px;color:#64748b;text-align:center;">
                Default Secret: <code style="color:#38bdf8;background:#020617;padding:2px 6px;border-radius:4px;">deployer_admin_secret_key_2026</code>
            </div>
        </div>
    </div>

<?php } else { ?>
    <!-- 2. AUTHENTICATED DASHBOARD -->

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="brand">
            <div class="brand-icon">🚀</div>
            <div class="brand-text">
                <h1>Kisiitv Newsroom Manager</h1>
                <p>Environment: <strong style="color:#10b981;">Production</strong> • DB: <strong style="color:#38bdf8;"><?= htmlspecialchars($stats['db_name'] ?: 'mysql') ?></strong></p>
            </div>
        </div>
        <div class="top-links">
            <a href="https://kisiitv.co.ke" target="_blank" class="btn-link btn-live">
                <span>🌐 View Website</span>
            </a>
            <a href="https://kisiitv.co.ke/login" target="_blank" class="btn-link btn-live" style="background:rgba(99,102,241,0.15);color:#818cf8;border-color:rgba(99,102,241,0.3);">
                <span>🔐 Admin Login</span>
            </a>
            <a href="?logout=1" class="btn-link btn-logout">
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Live Database Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-label">News Articles</div>
                <div class="stat-value"><?= (int) $stats['articles'] ?></div>
            </div>
            <div class="stat-status <?= $stats['articles'] > 0 ? 'status-ok' : 'status-warn' ?>">
                <?= $stats['articles'] > 0 ? '✓ Seeded' : '⚠️ Empty' ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">Categories</div>
                <div class="stat-value"><?= (int) $stats['categories'] ?></div>
            </div>
            <div class="stat-status <?= $stats['categories'] > 0 ? 'status-ok' : 'status-warn' ?>">
                <?= $stats['categories'] > 0 ? '✓ Ready' : '⚠️ Empty' ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">Admin Users</div>
                <div class="stat-value"><?= (int) $stats['users'] ?></div>
            </div>
            <div class="stat-status <?= $stats['users'] > 0 ? 'status-ok' : 'status-warn' ?>">
                <?= $stats['users'] > 0 ? '✓ Ready' : '⚠️ Need Admin' ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">Database Tables</div>
                <div class="stat-value"><?= (int) $stats['migrations'] ?></div>
            </div>
            <div class="stat-status <?= $stats['migrations'] > 0 ? 'status-ok' : 'status-warn' ?>">
                <?= $stats['migrations'] > 0 ? '✓ Migrated' : '⚠️ Pending' ?>
            </div>
        </div>
    </div>

    <!-- Output Box (if an action was executed) -->
    <?php if ($actionResult) { ?>
        <div class="alert-box" style="border-color: <?= $actionResult['success'] ? '#10b981' : '#ef4444' ?>;">
            <div class="alert-title" style="color: <?= $actionResult['success'] ? '#34d399' : '#f87171' ?>;">
                <?= htmlspecialchars($actionResult['title']) ?>
            </div>
            <div class="alert-output"><?= htmlspecialchars($actionResult['output']) ?></div>
        </div>
    <?php } ?>

    <!-- HERO: 1-Click Complete Setup (Migrate + Seed Articles) -->
    <div class="hero-card">
        <div class="hero-info">
            <h2><span>⚡</span> Complete Setup: Migrations & Content Seeding</h2>
            <p>
                Executes everything needed in a single click: runs database migrations (`migrate --force`), populates all news articles & categories (`db:seed --force`), creates default admin accounts, generates storage symlink, and clears cache.
            </p>
        </div>
        <div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="full_setup">
                <button type="submit" class="btn-hero" onclick="return confirm('Run complete database setup and seed content now?');">
                    <span>🚀 Run Full Setup & Seed Content</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Two Column Deck -->
    <div class="two-cols">
        <!-- Card 1: Admin Account Setup & Reset -->
        <div class="card">
            <div class="card-header">
                <h3><span>👤</span> Admin Login Credentials</h3>
                <span style="font-size:11px;color:#94a3b8;">Super Administrator</span>
            </div>

            <div class="creds-box">
                <div style="font-weight:700;color:#fff;margin-bottom:6px;">Default Newsroom Credentials:</div>
                <div class="creds-row">
                    <span>Email:</span>
                    <strong>superadmin@kisiitv.com</strong>
                </div>
                <div class="creds-row">
                    <span>Password:</span>
                    <strong>Password123!</strong>
                </div>
                <div style="margin-top:6px;font-size:11px;color:#94a3b8;">
                    Login URL: <a href="https://kisiitv.co.ke/login" target="_blank" style="color:#38bdf8;">kisiitv.co.ke/login</a>
                </div>
            </div>

            <h4 style="font-size:13px;font-weight:700;color:#ffffff;margin-bottom:12px;">Create or Reset Super Admin Account</h4>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="create_admin">
                <div class="form-group">
                    <label>Admin Full Name</label>
                    <input type="text" name="admin_name" value="Executive Super Admin" required class="input-text">
                </div>
                <div class="form-group">
                    <label>Admin Email Address</label>
                    <input type="email" name="admin_email" value="superadmin@kisiitv.com" required class="input-text">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="text" name="admin_password" value="Password123!" required class="input-text">
                </div>
                <button type="submit" class="btn btn-indigo" style="margin-top:8px;">
                    <span>Save / Reset Admin Account</span>
                </button>
            </form>
        </div>

        <!-- Card 2: Granular Utilities -->
        <div class="card">
            <div class="card-header">
                <h3><span>🛠️</span> Individual Operations</h3>
                <span style="font-size:11px;color:#94a3b8;">On-Demand</span>
            </div>

            <p style="font-size:12px;color:#94a3b8;margin-bottom:16px;">
                Run specific commands independently if you need to refresh caches, seed articles, or rerun migrations:
            </p>

            <div class="btn-grid" style="margin-bottom:12px;">
                <form method="POST" action="">
                    <input type="hidden" name="operation" value="seed">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Seed news articles and categories?');">
                        <span>🌱 Seed Articles</span>
                    </button>
                </form>

                <form method="POST" action="">
                    <input type="hidden" name="operation" value="migrate">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Run database migrations?');">
                        <span>🗄️ Run Migrations</span>
                    </button>
                </form>
            </div>

            <div class="btn-grid" style="margin-bottom:16px;">
                <form method="POST" action="">
                    <input type="hidden" name="operation" value="storage_link">
                    <button type="submit" class="btn btn-secondary">
                        <span>🔗 Storage Link</span>
                    </button>
                </form>

                <form method="POST" action="">
                    <input type="hidden" name="operation" value="cache_clear">
                    <button type="submit" class="btn btn-secondary">
                        <span>🧹 Clear Caches</span>
                    </button>
                </form>
            </div>

            <div style="background:#020617;border:1px solid #1e293b;border-radius:10px;padding:12px;font-size:11px;color:#94a3b8;">
                <div style="font-weight:700;color:#e2e8f0;margin-bottom:4px;">Direct Seed Link (Bookmark):</div>
                <div style="word-break:break-all;color:#38bdf8;font-family:ui-monospace,monospace;">
                    https://kisiitv.co.ke/deploy.php?run=full&key=<?= urlencode($secretKey) ?>
                </div>
            </div>
        </div>
    </div>

<?php } ?>

</div>

</body>
</html>
