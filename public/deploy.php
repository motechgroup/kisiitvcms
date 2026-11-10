<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Web Deployment & Operations Manager for Shared Hosting
 *
 * Provides a secure browser interface to:
 * - Track code changes & inspect incoming commits / diffs
 * - Pull latest changes from remote Git repository
 * - Run and rollback database migrations
 * - Rollback code to previous commits
 * - Manage Laravel cache, storage symlink, and maintenance mode
 * - Audit and record full deployment history
 *
 * @author Antigravity
 */

// 1. Hardening & Environment Setup
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

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Anti-caching and security headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

define('DEPLOYER_VERSION', '2.5.1');

// Intelligent BASE_PATH detection (works in public/ OR root public_html/)
if (file_exists(__DIR__.'/bootstrap/app.php')) {
    define('BASE_PATH', __DIR__);
} elseif (file_exists(dirname(__DIR__).'/bootstrap/app.php')) {
    define('BASE_PATH', dirname(__DIR__));
} else {
    define('BASE_PATH', __DIR__);
}

define('STORAGE_PATH', BASE_PATH.'/storage');
define('HISTORY_FILE', STORAGE_PATH.'/app/deployment_history.json');
define('ATTEMPTS_FILE', STORAGE_PATH.'/framework/cache/deploy_attempts.json');

// 2. Load Environment Configuration (.env)
function loadEnvConfig(): array
{
    $envPath = BASE_PATH.'/.env';
    $config = [
        'APP_NAME' => 'Laravel Application',
        'APP_ENV' => 'production',
        'APP_KEY' => '',
        'DEPLOY_SECRET' => '',
        'DEPLOY_ALLOWED_IPS' => '',
        'DEPLOY_DEFAULT_BRANCH' => 'main',
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

    // Default branch fallback: detect from git if possible
    return $config;
}

$envConfig = loadEnvConfig();

// Generate fallback secret if DEPLOY_SECRET is empty in .env (derive from APP_KEY)
$appKey = $envConfig['APP_KEY'] ?? '';
$configuredSecret = $envConfig['DEPLOY_SECRET'] ?? '';
$isDerivedSecret = false;

if (empty($configuredSecret)) {
    if (! empty($appKey)) {
        // Deterministic fallback derived from APP_KEY
        $configuredSecret = substr(hash('sha256', $appKey.'_deployer_secret_salt'), 0, 32);
        $isDerivedSecret = true;
    } else {
        $configuredSecret = 'admin12345'; // Emergency fallback
        $isDerivedSecret = true;
    }
}

// 3. IP Whitelist Restriction (if configured)
$allowedIps = array_filter(array_map('trim', explode(',', $envConfig['DEPLOY_ALLOWED_IPS'] ?? '')));
$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// If X-Forwarded-For contains comma-separated chain, take the first one
if (str_contains($clientIp, ',')) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}

if (! empty($allowedIps) && ! in_array($clientIp, $allowedIps, true)) {
    http_response_code(403);
    exit('<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>403 Forbidden</h2><p>Your IP ('.htmlspecialchars($clientIp).') is not authorized to access this deployment tool.</p></body></html>');
}

// 4. CSRF Protection Token
if (empty($_SESSION['deployer_csrf'])) {
    $_SESSION['deployer_csrf'] = bin2hex(random_bytes(32));
}

// 5. Brute Force Protection Helper
function checkBruteForce(): bool
{
    $maxAttempts = 5;
    $lockoutTime = 900; // 15 mins
    $ip = $GLOBALS['clientIp'];

    $attempts = [];
    if (file_exists(ATTEMPTS_FILE)) {
        $content = @file_get_contents(ATTEMPTS_FILE);
        $attempts = json_decode($content, true) ?: [];
    }

    if (isset($attempts[$ip])) {
        $record = $attempts[$ip];
        if ($record['count'] >= $maxAttempts && (time() - $record['last_time']) < $lockoutTime) {
            return false; // locked out
        }
        if ((time() - $record['last_time']) >= $lockoutTime) {
            unset($attempts[$ip]);
            @file_put_contents(ATTEMPTS_FILE, json_encode($attempts));
        }
    }

    return true;
}

function recordFailedAttempt(): void
{
    $ip = $GLOBALS['clientIp'];
    $attempts = [];
    if (file_exists(ATTEMPTS_FILE)) {
        $content = @file_get_contents(ATTEMPTS_FILE);
        $attempts = json_decode($content, true) ?: [];
    }
    $record = $attempts[$ip] ?? ['count' => 0, 'last_time' => 0];
    $record['count']++;
    $record['last_time'] = time();
    $attempts[$ip] = $record;

    @mkdir(dirname(ATTEMPTS_FILE), 0755, true);
    @file_put_contents(ATTEMPTS_FILE, json_encode($attempts));
}

function resetFailedAttempts(): void
{
    $ip = $GLOBALS['clientIp'];
    if (file_exists(ATTEMPTS_FILE)) {
        $content = @file_get_contents(ATTEMPTS_FILE);
        $attempts = json_decode($content, true) ?: [];
        if (isset($attempts[$ip])) {
            unset($attempts[$ip]);
            @file_put_contents(ATTEMPTS_FILE, json_encode($attempts));
        }
    }
}

// 6. Handle Authentication
$authError = null;

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['deployer_authenticated']);
    header('Location: '.strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_secret'])) {
    if (! checkBruteForce()) {
        $authError = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    } else {
        $enteredSecret = (string) $_POST['login_secret'];
        if (hash_equals($configuredSecret, $enteredSecret)) {
            $_SESSION['deployer_authenticated'] = true;
            resetFailedAttempts();
            header('Location: '.strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        } else {
            recordFailedAttempt();
            $authError = 'Invalid deployment secret key.';
        }
    }
}

$isAuthenticated = ! empty($_SESSION['deployer_authenticated']);

// 7. Core Process & Execution Helpers
class ProcessRunner
{
    public static function run(string $command, string $cwd = BASE_PATH): array
    {
        $startTime = microtime(true);
        $output = '';
        $exitCode = -1;
        $error = '';

        // Prevent Git from hanging waiting for interactive passwords / credentials
        $env = array_merge($_ENV, [
            'GIT_TERMINAL_PROMPT' => '0',
            'GIT_SSH_COMMAND' => 'ssh -o BatchMode=yes',
            'HOME' => getenv('HOME') ?: BASE_PATH,
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
        ]);

        if (function_exists('proc_open')) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($command, $descriptors, $pipes, $cwd, $env);
            if (is_resource($process)) {
                @fclose($pipes[0]);
                $output = @stream_get_contents($pipes[1]);
                $error = @stream_get_contents($pipes[2]);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                $exitCode = @proc_close($process);
            } else {
                $error = "proc_open failed to execute command: {$command}";
            }
        } elseif (function_exists('exec')) {
            $execOutput = [];
            @exec('cd '.escapeshellarg($cwd)." && {$command} 2>&1", $execOutput, $exitCode);
            $output = implode("\n", $execOutput);
        } elseif (function_exists('shell_exec')) {
            $output = (string) @shell_exec('cd '.escapeshellarg($cwd)." && {$command} 2>&1");
            $exitCode = empty($output) ? 1 : 0;
        } else {
            return [
                'success' => false,
                'exit_code' => -1,
                'output' => '',
                'error' => 'Command execution is blocked: proc_open, exec, and shell_exec are all disabled on this server.',
                'duration' => 0,
            ];
        }

        $duration = round(microtime(true) - $startTime, 3);
        $combinedOutput = trim($output.(! empty($error) ? "\n".$error : ''));

        return [
            'success' => ($exitCode === 0),
            'exit_code' => $exitCode,
            'output' => $combinedOutput,
            'error' => $error,
            'duration' => $duration,
        ];
    }
}

// 8. Laravel Artisan In-Process Runner
class ArtisanRunner
{
    protected static $bootstrapped = false;

    protected static $app = null;

    public static function bootstrap(): bool
    {
        if (self::$bootstrapped) {
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

            // In Laravel 11/12/13
            if (method_exists(self::$app, 'make')) {
                $kernel = self::$app->make(Kernel::class);
                $kernel->bootstrap();
            }
            self::$bootstrapped = true;

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function call(string $command, array $parameters = []): array
    {
        $startTime = microtime(true);
        if (! self::bootstrap()) {
            // Fallback to CLI command if internal bootstrapping fails
            $cliParams = '';
            foreach ($parameters as $k => $v) {
                if ($v === true) {
                    $cliParams .= ' '.escapeshellarg($k);
                } elseif ($v !== false && $v !== null) {
                    $cliParams .= ' '.escapeshellarg("{$k}={$v}");
                }
            }

            return ProcessRunner::run("php artisan {$command}{$cliParams}");
        }

        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();
            $duration = round(microtime(true) - $startTime, 3);

            return [
                'success' => ($exitCode === 0),
                'exit_code' => $exitCode,
                'output' => trim($output),
                'error' => ($exitCode !== 0) ? 'Command exited with status '.$exitCode : '',
                'duration' => $duration,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'exit_code' => -1,
                'output' => '',
                'error' => 'Artisan Exception: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine(),
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }
    }
}

// 9. Deployment State & History Manager
class DeploymentHistory
{
    public static function log(string $action, string $status, string $output, float $duration, ?string $commitBefore = null, ?string $commitAfter = null): void
    {
        $history = self::getAll();

        $entry = [
            'id' => uniqid('dep_', true),
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s T'),
            'action' => $action,
            'status' => $status,
            'duration' => $duration,
            'ip' => $GLOBALS['clientIp'],
            'commit_before' => $commitBefore,
            'commit_after' => $commitAfter,
            'output' => $output,
        ];

        array_unshift($history, $entry);
        $history = array_slice($history, 0, 50); // Keep last 50

        @mkdir(dirname(HISTORY_FILE), 0755, true);
        @file_put_contents(HISTORY_FILE, json_encode($history, JSON_PRETTY_PRINT));
    }

    public static function getAll(): array
    {
        if (! file_exists(HISTORY_FILE)) {
            return [];
        }
        $data = @file_get_contents(HISTORY_FILE);

        return json_decode($data, true) ?: [];
    }
}

// 10. Git Information Provider
class GitManager
{
    public static function getCurrentCommit(): array
    {
        $res = ProcessRunner::run('git log -1 --pretty=format:"%H|%h|%an|%ae|%ar|%s"');
        if (! $res['success'] || empty($res['output'])) {
            return [
                'hash' => 'N/A',
                'short' => 'N/A',
                'author' => 'Unknown',
                'email' => '',
                'date' => 'N/A',
                'subject' => 'No git repository detected or git error',
            ];
        }
        $parts = explode('|', $res['output'], 6);

        return [
            'hash' => $parts[0] ?? '',
            'short' => $parts[1] ?? '',
            'author' => $parts[2] ?? '',
            'email' => $parts[3] ?? '',
            'date' => $parts[4] ?? '',
            'subject' => $parts[5] ?? '',
        ];
    }

    public static function getBranch(): string
    {
        $res = ProcessRunner::run('git rev-parse --abbrev-ref HEAD');

        return $res['success'] ? trim($res['output']) : 'main';
    }

    public static function getRemoteUrl(): string
    {
        $res = ProcessRunner::run('git config --get remote.origin.url');

        return $res['success'] ? trim($res['output']) : 'Not configured';
    }

    public static function getRecentCommits(int $count = 15): array
    {
        $res = ProcessRunner::run("git log -n {$count} --pretty=format:\"%H|%h|%an|%ar|%s\"");
        if (! $res['success'] || empty($res['output'])) {
            return [];
        }
        $commits = [];
        $lines = explode("\n", trim($res['output']));
        foreach ($lines as $line) {
            $parts = explode('|', $line, 5);
            if (count($parts) >= 5) {
                $commits[] = [
                    'hash' => $parts[0],
                    'short' => $parts[1],
                    'author' => $parts[2],
                    'date' => $parts[3],
                    'subject' => $parts[4],
                ];
            }
        }

        return $commits;
    }

    public static function checkUpdates(?string $branch = null): array
    {
        $branch = $branch ?: self::getBranch();

        // 1. Fetch remote origin
        $fetchRes = ProcessRunner::run("git fetch origin {$branch}");

        // 2. Log of incoming commits (HEAD..origin/branch)
        $logRes = ProcessRunner::run("git log HEAD..origin/{$branch} --pretty=format:\"%H|%h|%an|%ar|%s\"");

        $incomingCommits = [];
        if ($logRes['success'] && ! empty(trim($logRes['output']))) {
            $lines = explode("\n", trim($logRes['output']));
            foreach ($lines as $line) {
                $parts = explode('|', $line, 5);
                if (count($parts) >= 5) {
                    $incomingCommits[] = [
                        'hash' => $parts[0],
                        'short' => $parts[1],
                        'author' => $parts[2],
                        'date' => $parts[3],
                        'subject' => $parts[4],
                    ];
                }
            }
        }

        // 3. Diff stat between local and remote
        $diffStatRes = ProcessRunner::run("git diff HEAD origin/{$branch} --stat");

        // 4. Local uncommitted changes
        $statusRes = ProcessRunner::run('git status --short');
        $hasLocalChanges = ! empty(trim($statusRes['output']));

        return [
            'success' => $fetchRes['success'],
            'branch' => $branch,
            'incoming_count' => count($incomingCommits),
            'incoming_commits' => $incomingCommits,
            'diff_stat' => trim($diffStatRes['output']),
            'local_changes' => trim($statusRes['output']),
            'has_local_changes' => $hasLocalChanges,
            'raw_fetch' => $fetchRes['output'] ?: 'Up to date with origin/'.$branch,
        ];
    }
}

// 11. Handle AJAX Actions
if ($isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Verify CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (! hash_equals($_SESSION['deployer_csrf'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'CSRF token mismatch. Please reload the page.']);
        exit;
    }

    $action = $_POST['action'];
    $response = ['success' => false, 'action' => $action, 'output' => ''];
    $commitBefore = GitManager::getCurrentCommit()['short'];

    switch ($action) {
        case 'get_status':
            $currentCommit = GitManager::getCurrentCommit();
            $branch = GitManager::getBranch();
            $remote = GitManager::getRemoteUrl();
            $isDown = file_exists(STORAGE_PATH.'/framework/down') || file_exists(STORAGE_PATH.'/framework/maintenance.php');

            // Check DB connection
            $dbOk = false;
            $dbMsg = '';
            try {
                if (ArtisanRunner::bootstrap()) {
                    DB::connection()->getPdo();
                    $dbOk = true;
                    $dbMsg = 'Connected ('.DB::connection()->getDatabaseName().')';
                }
            } catch (Throwable $e) {
                $dbMsg = $e->getMessage();
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'commit' => $currentCommit,
                    'branch' => $branch,
                    'remote' => $remote,
                    'maintenance' => $isDown,
                    'db_ok' => $dbOk,
                    'db_message' => $dbMsg,
                    'app_name' => $envConfig['APP_NAME'] ?? 'Laravel',
                    'app_env' => $envConfig['APP_ENV'] ?? 'production',
                ],
            ]);
            exit;

        case 'check_updates':
            $branch = ! empty($_POST['branch']) ? trim($_POST['branch']) : GitManager::getBranch();
            $res = GitManager::checkUpdates($branch);
            echo json_encode(['success' => true, 'data' => $res]);
            exit;

        case 'git_status':
            $res = ProcessRunner::run('git status');
            echo json_encode($res);
            exit;

        case 'git_diff':
            $res = ProcessRunner::run('git diff');
            echo json_encode($res);
            exit;

        case 'git_stash':
            $res = ProcessRunner::run('git stash save "Stashed from Web Deployer at '.date('Y-m-d H:i:s').'"');
            DeploymentHistory::log('Git Stash', $res['success'] ? 'success' : 'failed', $res['output'], $res['duration']);
            echo json_encode($res);
            exit;

        case 'git_log':
            $commits = GitManager::getRecentCommits(20);
            echo json_encode(['success' => true, 'commits' => $commits]);
            exit;

        case 'git_pull':
            $branch = ! empty($_POST['branch']) ? escapeshellarg(trim($_POST['branch'])) : GitManager::getBranch();
            $res = ProcessRunner::run("git pull origin {$branch}");
            $commitAfter = GitManager::getCurrentCommit()['short'];
            DeploymentHistory::log("Git Pull ({$branch})", $res['success'] ? 'success' : 'failed', $res['output'], $res['duration'], $commitBefore, $commitAfter);
            echo json_encode($res);
            exit;

        case 'migrate':
            $res = ArtisanRunner::call('migrate', ['--force' => true]);
            DeploymentHistory::log('Database Migrate', $res['success'] ? 'success' : 'failed', $res['output'] ?: $res['error'], $res['duration']);
            echo json_encode($res);
            exit;

        case 'migrate_status':
            $res = ArtisanRunner::call('migrate:status');
            echo json_encode($res);
            exit;

        case 'migrate_rollback':
            $step = max(1, (int) ($_POST['step'] ?? 1));
            $res = ArtisanRunner::call('migrate:rollback', ['--force' => true, '--step' => $step]);
            DeploymentHistory::log("Migrate Rollback (step={$step})", $res['success'] ? 'success' : 'failed', $res['output'] ?: $res['error'], $res['duration']);
            echo json_encode($res);
            exit;

        case 'git_rollback':
            $targetCommit = trim($_POST['target_commit'] ?? '');
            if (empty($targetCommit) || ! preg_match('/^[a-f0-9]{5,40}$/i', $targetCommit)) {
                echo json_encode(['success' => false, 'error' => 'Invalid target commit hash provided.']);
                exit;
            }
            $res = ProcessRunner::run('git reset --hard '.escapeshellarg($targetCommit));
            $commitAfter = GitManager::getCurrentCommit()['short'];
            // Clear caches after rollback
            ArtisanRunner::call('optimize:clear');
            DeploymentHistory::log("Git Rollback ({$targetCommit})", $res['success'] ? 'success' : 'failed', $res['output'], $res['duration'], $commitBefore, $commitAfter);
            echo json_encode($res);
            exit;

        case 'cache_clear':
            $res = ArtisanRunner::call('optimize:clear');
            DeploymentHistory::log('Cache Clear (optimize:clear)', $res['success'] ? 'success' : 'failed', $res['output'] ?: $res['error'], $res['duration']);
            echo json_encode($res);
            exit;

        case 'cache_all':
            $log = [];
            $totalDuration = 0;
            $commands = [
                'config:cache',
                'route:cache',
                'view:cache',
            ];
            $allSuccess = true;
            foreach ($commands as $cmd) {
                $r = ArtisanRunner::call($cmd);
                $totalDuration += $r['duration'];
                $log[] = ">>> php artisan {$cmd}\n".($r['output'] ?: $r['error']);
                if (! $r['success']) {
                    $allSuccess = false;
                }
            }
            $finalOutput = implode("\n\n", $log);
            DeploymentHistory::log('Optimize Cache (config, route, view)', $allSuccess ? 'success' : 'failed', $finalOutput, $totalDuration);
            echo json_encode(['success' => $allSuccess, 'output' => $finalOutput, 'duration' => $totalDuration]);
            exit;

        case 'storage_link':
            $res = ArtisanRunner::call('storage:link');
            // Check if public/storage symlink exists
            if (! file_exists(BASE_PATH.'/public/storage') && file_exists(STORAGE_PATH.'/app/public')) {
                @symlink(STORAGE_PATH.'/app/public', BASE_PATH.'/public/storage');
                $res['output'] .= "\nNative PHP symlink() fallback executed.";
            }
            DeploymentHistory::log('Storage Link', $res['success'] ? 'success' : 'failed', $res['output'] ?: $res['error'], $res['duration']);
            echo json_encode($res);
            exit;

        case 'maintenance_toggle':
            $isDown = file_exists(STORAGE_PATH.'/framework/down') || file_exists(STORAGE_PATH.'/framework/maintenance.php');
            if ($isDown) {
                $res = ArtisanRunner::call('up');
                $msg = 'Application is now LIVE (maintenance mode disabled).';
            } else {
                $res = ArtisanRunner::call('down');
                $msg = 'Application is now in MAINTENANCE MODE.';
            }
            $res['message'] = $msg;
            DeploymentHistory::log($isDown ? 'Maintenance Mode OFF' : 'Maintenance Mode ON', $res['success'] ? 'success' : 'failed', $res['output'] ?: $msg, $res['duration']);
            echo json_encode($res);
            exit;

        case 'full_deploy':
            $startTime = microtime(true);
            $branch = ! empty($_POST['branch']) ? trim($_POST['branch']) : GitManager::getBranch();
            $enableMaintenance = ! empty($_POST['with_maintenance']);
            $optimizeCache = ! empty($_POST['with_cache']);
            $log = [];
            $allSuccess = true;

            $log[] = '=======================================================';
            $log[] = '🚀 FULL AUTOMATED DEPLOYMENT PIPELINE STARTED';
            $log[] = 'Time: '.date('Y-m-d H:i:s T');
            $log[] = 'Target Branch: '.$branch;
            $log[] = 'Starting Commit: '.$commitBefore;
            $log[] = "=======================================================\n";

            // Step 1: Maintenance Mode ON
            if ($enableMaintenance) {
                $log[] = '[Step 1/6] 🛡️ Enabling Maintenance Mode...';
                $r = ArtisanRunner::call('down');
                $log[] = ($r['output'] ?: 'Application put into maintenance mode.')."\n";
            } else {
                $log[] = "[Step 1/6] ⏩ Maintenance mode skipped (Zero-downtime requested).\n";
            }

            // Step 2: Git Pull
            $log[] = "[Step 2/6] 📥 Pulling latest code from origin/{$branch}...";
            $r = ProcessRunner::run('git pull origin '.escapeshellarg($branch));
            $log[] = $r['output'] ?: $r['error'];
            if (! $r['success']) {
                $log[] = '❌ ERROR: Git pull failed!';
                $allSuccess = false;
            }
            $log[] = '';

            // Step 3: Run Database Migrations
            $log[] = '[Step 3/6] 🗄️ Running Database Migrations...';
            $r = ArtisanRunner::call('migrate', ['--force' => true]);
            $log[] = $r['output'] ?: ($r['success'] ? 'Nothing to migrate.' : $r['error']);
            if (! $r['success']) {
                $log[] = '❌ WARNING: Database migration encountered an issue.';
            }
            $log[] = '';

            // Step 4: Clear & Refresh Caches
            $log[] = '[Step 4/6] 🧹 Clearing application caches...';
            $r = ArtisanRunner::call('optimize:clear');
            $log[] = $r['output'] ?: 'Caches cleared.';
            $log[] = '';

            // Step 5: Storage Link & Optional Cache Optimization
            $log[] = '[Step 5/6] 🔗 Verifying storage symlink & optimization...';
            $r = ArtisanRunner::call('storage:link');
            if ($optimizeCache) {
                $log[] = 'Rebuilding optimized production caches (config, routes, views)...';
                ArtisanRunner::call('config:cache');
                ArtisanRunner::call('route:cache');
                ArtisanRunner::call('view:cache');
            }
            $log[] = "Storage link and caching completed.\n";

            // Step 6: Bring application back Live
            if ($enableMaintenance) {
                $log[] = '[Step 6/6] 🌐 Bringing application back LIVE...';
                $r = ArtisanRunner::call('up');
                $log[] = ($r['output'] ?: 'Application is now live.')."\n";
            } else {
                $log[] = "[Step 6/6] 🌐 Deployment finished in live mode.\n";
            }

            $totalDuration = round(microtime(true) - $startTime, 3);
            $commitAfter = GitManager::getCurrentCommit()['short'];

            $log[] = '=======================================================';
            $log[] = $allSuccess ? '✅ DEPLOYMENT FINISHED SUCCESSFULLY!' : '⚠️ DEPLOYMENT FINISHED WITH WARNINGS/ERRORS';
            $log[] = "Previous Commit: {$commitBefore} | New Commit: {$commitAfter}";
            $log[] = "Total Duration: {$totalDuration}s";
            $log[] = '=======================================================';

            $finalOutput = implode("\n", $log);
            DeploymentHistory::log("Full Deployment ({$branch})", $allSuccess ? 'success' : 'failed', $finalOutput, $totalDuration, $commitBefore, $commitAfter);

            echo json_encode([
                'success' => $allSuccess,
                'output' => $finalOutput,
                'duration' => $totalDuration,
                'commit_before' => $commitBefore,
                'commit_after' => $commitAfter,
            ]);
            exit;

        case 'get_history':
            echo json_encode(['success' => true, 'history' => DeploymentHistory::getAll()]);
            exit;

        case 'get_laravel_logs':
            $logFile = STORAGE_PATH.'/logs/laravel.log';
            if (! file_exists($logFile)) {
                echo json_encode(['success' => true, 'output' => 'laravel.log does not exist yet.']);
                exit;
            }
            // Read last 60 lines
            $lines = [];
            $fp = @fopen($logFile, 'r');
            if ($fp) {
                $buffer = '';
                $pos = -2;
                $lineCount = 0;
                $targetLines = 60;
                @fseek($fp, 0, SEEK_END);
                $fileSize = ftell($fp);
                while ($fileSize + $pos >= 0 && $lineCount < $targetLines) {
                    fseek($fp, $pos, SEEK_END);
                    $char = fgetc($fp);
                    if ($char === "\n") {
                        $lineCount++;
                    }
                    $pos--;
                }
                $output = fread($fp, abs($pos));
                fclose($fp);
                echo json_encode(['success' => true, 'output' => $output]);
                exit;
            }
            echo json_encode(['success' => false, 'error' => 'Unable to read laravel.log file.']);
            exit;

        case 'get_diagnostics':
            $disabledFunctions = explode(',', ini_get('disable_functions') ?: '');
            $disabledFunctions = array_filter(array_map('trim', $disabledFunctions));

            $storageWritable = is_writable(STORAGE_PATH);
            $cacheWritable = is_writable(BASE_PATH.'/bootstrap/cache');
            $gitFolderExists = is_dir(BASE_PATH.'/.git');

            $phpProcOpen = function_exists('proc_open') && ! in_array('proc_open', $disabledFunctions, true);
            $phpExec = function_exists('exec') && ! in_array('exec', $disabledFunctions, true);
            $phpShellExec = function_exists('shell_exec') && ! in_array('shell_exec', $disabledFunctions, true);

            $gitVersion = ProcessRunner::run('git --version');

            echo json_encode([
                'success' => true,
                'data' => [
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'disabled_functions' => $disabledFunctions,
                    'storage_writable' => $storageWritable,
                    'cache_writable' => $cacheWritable,
                    'git_exists' => $gitFolderExists,
                    'proc_open_available' => $phpProcOpen,
                    'exec_available' => $phpExec,
                    'shell_exec_available' => $phpShellExec,
                    'git_version' => $gitVersion['success'] ? $gitVersion['output'] : 'Git not found or command blocked',
                ],
            ]);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => "Unknown action '{$action}'."]);
            exit;
    }
}

// 12. View Rendering
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Deployer & Operations Manager — <?= htmlspecialchars($envConfig['APP_NAME'] ?? 'Laravel') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; background: #030712; color: #f3f4f6; min-height: 100vh; }
        code, pre, .mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0b0f19; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }

        .glow-emerald { box-shadow: 0 0 25px -5px rgba(16, 185, 129, 0.25); }
        .glow-indigo { box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.25); }
        .tab-btn.active { background: #1f2937; color: #38bdf8; border-bottom: 2px solid #38bdf8; }
        
        /* Console ANSI simulation */
        .terminal-window { background: #070b14; border: 1px solid #1e293b; border-radius: 12px; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<?php if (! $isAuthenticated) { ?>
<!-- LOGIN SCREEN -->
<div class="flex-1 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-2xl p-8 shadow-2xl backdrop-blur-xl">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center font-bold text-white shadow-lg shadow-sky-500/20">
                🚀
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-white"><?= htmlspecialchars($envConfig['APP_NAME'] ?? 'Laravel') ?></h1>
                <p class="text-xs text-slate-400">Web Deployment & Operations Manager</p>
            </div>
        </div>

        <?php if ($authError) { ?>
            <div class="mb-4 p-3.5 bg-rose-950/60 border border-rose-800/80 rounded-xl text-xs text-rose-300 flex items-start space-x-2">
                <span class="text-sm">⚠️</span>
                <span><?= htmlspecialchars($authError) ?></span>
            </div>
        <?php } ?>

        <?php if ($isDerivedSecret) { ?>
            <div class="mb-5 p-3.5 bg-amber-950/40 border border-amber-800/50 rounded-xl text-xs text-amber-200">
                <p class="font-semibold mb-1">🔑 First-time setup / Default Key:</p>
                <p class="text-slate-300 mb-2">No <code class="bg-amber-900/40 px-1 py-0.5 rounded text-amber-300">DEPLOY_SECRET</code> was found in your <code class="text-amber-300">.env</code>. A fallback secret was derived from your <code class="text-amber-300">APP_KEY</code>:</p>
                <div class="bg-black/50 p-2 rounded border border-amber-900/40 select-all font-mono text-[11px] break-all text-amber-400">
                    <?= htmlspecialchars($configuredSecret) ?>
                </div>
                <p class="text-[10px] text-slate-400 mt-2">To set a custom secret, add <code class="text-amber-300">DEPLOY_SECRET=your_password</code> in your <code class="text-amber-300">.env</code> file.</p>
            </div>
        <?php } ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label for="login_secret" class="block text-xs font-medium text-slate-300 mb-1.5">Deployment Secret Key</label>
                <input type="password" id="login_secret" name="login_secret" required autofocus placeholder="Enter your secret key..."
                    class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-sky-500 hover:from-sky-400 to-indigo-600 hover:to-indigo-500 text-white font-semibold py-3 px-4 rounded-xl text-sm shadow-lg shadow-sky-500/25 transition duration-150 flex items-center justify-center space-x-2">
                <span>Unlock Deployment Manager</span>
                <span>→</span>
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
            <span>Security: Protected Session</span>
            <span>v<?= DEPLOYER_VERSION ?></span>
        </div>
    </div>
</div>

<?php } else { ?>
<!-- AUTHENTICATED DASHBOARD -->
<header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
        <!-- Logo & App Title -->
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center font-bold text-white shadow-md shadow-sky-500/20 text-base">
                🚀
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-sm font-bold text-white tracking-wide"><?= htmlspecialchars($envConfig['APP_NAME'] ?? 'Laravel') ?></h1>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">v<?= DEPLOYER_VERSION ?></span>
                    <span id="badge-env" class="text-[10px] uppercase font-semibold px-2 py-0.5 rounded-full bg-indigo-950/70 text-indigo-400 border border-indigo-800">
                        <?= htmlspecialchars($envConfig['APP_ENV'] ?? 'production') ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 flex items-center space-x-2">
                    <span>Branch: <strong id="header-branch" class="text-sky-400 mono">loading...</strong></span>
                    <span>•</span>
                    <span>Commit: <strong id="header-commit" class="text-emerald-400 mono">loading...</strong></span>
                </p>
            </div>
        </div>

        <!-- Quick Status Pills -->
        <div class="flex items-center space-x-3 text-xs">
            <div id="status-db" class="px-2.5 py-1 rounded-lg bg-slate-800/70 border border-slate-700 text-slate-300 flex items-center space-x-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="text-[11px]">DB: Checking...</span>
            </div>

            <div id="status-maint" class="px-2.5 py-1 rounded-lg bg-slate-800/70 border border-slate-700 text-slate-300 flex items-center space-x-1.5 cursor-pointer hover:border-slate-500 transition" onclick="toggleMaintenance()">
                <span id="maint-indicator" class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span id="maint-text" class="text-[11px]">App: Live</span>
            </div>

            <button onclick="refreshStatus()" title="Refresh System Status" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                <svg id="refresh-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>

            <a href="?action=logout" class="px-3 py-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900/60 text-rose-300 border border-rose-800/80 text-[11px] font-medium transition">
                Logout
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800/60 flex space-x-1 text-xs overflow-x-auto">
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition active" data-tab="tab-deploy">
            🚀 Deploy & Actions
        </button>
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition" data-tab="tab-changes">
            📦 Incoming Commits & Diff <span id="badge-incoming" class="hidden ml-1 px-1.5 py-0.2 rounded-full bg-sky-500 text-white text-[10px]">0</span>
        </button>
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition" data-tab="tab-migrations">
            🗄️ Database & Migrations
        </button>
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition" data-tab="tab-rollback">
            ⏪ Rollback Center
        </button>
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition" data-tab="tab-history">
            📜 History & Audit
        </button>
        <button class="tab-btn px-4 py-2.5 font-medium text-slate-400 hover:text-white transition" data-tab="tab-diagnostics">
            🩺 Server & Logs
        </button>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 space-y-6 w-full">

    <!-- TAB 1: DEPLOY & ACTIONS -->
    <div id="tab-deploy" class="tab-content space-y-6">
        <!-- Hero: 1-Click Automated Deployment Pipeline -->
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/60 border border-indigo-900/40 rounded-2xl p-6 shadow-xl">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-2 max-w-xl">
                    <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-full bg-indigo-950/80 border border-indigo-800/80 text-[11px] text-indigo-300 font-medium">
                        <span>⚡</span>
                        <span>Complete One-Click Pipeline</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-white tracking-tight">Production Automated Deployment</h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Executes zero-fuss sequential pipeline: <strong>Git Pull</strong> → <strong>Database Migrations</strong> (`migrate --force`) → <strong>Optimize Clear</strong> → <strong>Rebuild Cache</strong> → <strong>Storage Symlink</strong>.
                    </p>
                    
                    <div class="flex flex-wrap items-center gap-4 pt-1 text-xs text-slate-300">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="deploy-maint" class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
                            <span>Enable Maintenance Mode during deploy</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="deploy-cache" checked class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
                            <span>Rebuild production caches</span>
                        </label>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-slate-400">Branch:</span>
                            <input type="text" id="deploy-branch" value="main" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-xs text-sky-400 font-mono w-24 text-center">
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-auto flex flex-col items-center gap-2">
                    <button id="btn-full-deploy" onclick="triggerFullDeploy()"
                        class="w-full md:w-auto px-7 py-4 rounded-xl bg-gradient-to-r from-emerald-500 hover:from-emerald-400 to-teal-600 hover:to-teal-500 text-white font-bold text-sm shadow-xl shadow-emerald-500/25 transition duration-150 flex items-center justify-center space-x-3 glow-emerald">
                        <span class="text-lg">🚀</span>
                        <span>Deploy Now</span>
                    </button>
                    <span class="text-[11px] text-slate-400">Takes approx 3 - 8 seconds</span>
                </div>
            </div>
        </div>

        <!-- Granular Action Deck -->
        <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Granular Operations & Utilities</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Git Pull Only -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>📥</span>
                                <span>Git Pull</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">origin</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Pull code updates from repository without running migrations or caches.</p>
                    </div>
                    <button onclick="runAction('git_pull')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Pull Changes
                    </button>
                </div>

                <!-- Run Migrations Only -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>🗄️</span>
                                <span>Run Migrations</span>
                            </span>
                            <span class="text-[10px] text-amber-400 mono">--force</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Executes any pending database schema migrations safely in-process.</p>
                    </div>
                    <button onclick="runAction('migrate')" class="w-full py-2 bg-indigo-950/80 hover:bg-indigo-900/80 text-indigo-300 border border-indigo-800/80 text-xs font-medium rounded-lg transition">
                        Run Migrations
                    </button>
                </div>

                <!-- Clear Caches -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>🧹</span>
                                <span>Clear All Caches</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">optimize:clear</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Flushes config, route, view, and compiled application caches immediately.</p>
                    </div>
                    <button onclick="runAction('cache_clear')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Flush Cache
                    </button>
                </div>

                <!-- Maintenance Mode -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>🛡️</span>
                                <span>Maintenance Mode</span>
                            </span>
                            <span id="card-maint-status" class="text-[10px] text-emerald-400 mono">LIVE</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Toggle application maintenance mode (`php artisan down` / `up`).</p>
                    </div>
                    <button onclick="toggleMaintenance()" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Toggle Mode
                    </button>
                </div>

                <!-- Create Storage Link -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>🔗</span>
                                <span>Storage Symlink</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">public/storage</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Links `storage/app/public` to `public/storage` for uploads and images.</p>
                    </div>
                    <button onclick="runAction('storage_link')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Link Storage
                    </button>
                </div>

                <!-- Git Stash -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>📦</span>
                                <span>Git Stash</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">git stash</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Temporarily stash away local modified files if git pull reports merge conflicts.</p>
                    </div>
                    <button onclick="runAction('git_stash')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Stash Changes
                    </button>
                </div>

                <!-- Git Status -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>🔍</span>
                                <span>Git Working Tree</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">git status</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Inspect local repository working directory state and uncommitted files.</p>
                    </div>
                    <button onclick="runAction('git_status')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Check Status
                    </button>
                </div>

                <!-- Cache Optimization -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                <span>⚡</span>
                                <span>Rebuild Caches</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mono">routes & config</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Compiles config, routes, and views into cached files for fastest performance.</p>
                    </div>
                    <button onclick="runAction('cache_all')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg transition">
                        Cache Everything
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- TAB 2: INCOMING COMMITS & DIFF TRACKER -->
    <div id="tab-changes" class="tab-content hidden space-y-6">
        <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>📦</span>
                        <span>Track Remote Changes & Incoming Commits</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Fetches remote origin and inspects all new commits ahead before you pull.</p>
                </div>
                <button onclick="checkRemoteUpdates()" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-medium transition flex items-center space-x-2">
                    <span>🔄 Fetch Origin & Check Updates</span>
                </button>
            </div>

            <!-- Update Summary Container -->
            <div id="changes-summary" class="space-y-4">
                <div class="text-center py-8 text-slate-500 text-xs">
                    Click "Fetch Origin & Check Updates" to check for pending commits from GitHub/remote repository.
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: DATABASE & MIGRATIONS -->
    <div id="tab-migrations" class="tab-content hidden space-y-6">
        <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>🗄️</span>
                        <span>Database Migrations Manager</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Inspect executed vs pending migrations and execute schema changes.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="runAction('migrate_status')" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-medium transition">
                        View Migration Status
                    </button>
                    <button onclick="runAction('migrate')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-medium transition">
                        Run Migrations (--force)
                    </button>
                </div>
            </div>

            <div id="migrations-output" class="text-xs text-slate-400 font-mono bg-black/40 p-4 rounded-xl border border-slate-800 whitespace-pre-wrap max-h-96 overflow-y-auto">
Click "View Migration Status" or "Run Migrations" to inspect your database state.
            </div>
        </div>
    </div>

    <!-- TAB 4: ROLLBACK CENTER -->
    <div id="tab-rollback" class="tab-content hidden space-y-6">
        <div class="bg-rose-950/20 border border-rose-900/40 rounded-2xl p-6">
            <div class="flex items-center space-x-3 mb-2">
                <span class="text-xl">⚠️</span>
                <h3 class="text-lg font-bold text-rose-300">Rollback Center & Emergency Recovery</h3>
            </div>
            <p class="text-xs text-slate-300 mb-6">
                Use these tools if a deployment caused an error or broken state. You can revert database migrations, reset git code to a previous commit, or perform both.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Migration Rollback -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-white flex items-center space-x-2">
                            <span>🗄️</span>
                            <span>Database Migration Rollback</span>
                        </h4>
                        <span class="text-[10px] text-amber-400 mono">migrate:rollback</span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Rolls back the last batch of database migrations.
                    </p>
                    <div class="flex items-center space-x-3">
                        <label class="text-xs text-slate-300">Steps to revert:</label>
                        <select id="rollback-steps" class="bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
                            <option value="1">1 Batch / Step (Recommended)</option>
                            <option value="2">2 Batches / Steps</option>
                            <option value="3">3 Batches / Steps</option>
                        </select>
                    </div>
                    <button onclick="triggerMigrateRollback()" class="w-full py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold rounded-lg transition shadow-md shadow-amber-600/20">
                        Rollback Database Migrations
                    </button>
                </div>

                <!-- Code Rollback (Git Reset) -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-white flex items-center space-x-2">
                            <span>⏪</span>
                            <span>Code Rollback (Git Reset)</span>
                        </h4>
                        <span class="text-[10px] text-rose-400 mono">git reset --hard</span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Reverts your working directory files to a chosen previous commit and automatically clears cache.
                    </p>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Select previous commit:</label>
                        <select id="rollback-commit-select" class="w-full bg-slate-950 border border-slate-700 rounded px-3 py-2 text-xs text-sky-300 font-mono">
                            <option value="">Loading recent commits...</option>
                        </select>
                    </div>
                    <button onclick="triggerGitRollback()" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-lg transition shadow-md shadow-rose-600/20">
                        Rollback Code to Selected Commit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 5: DEPLOYMENT HISTORY & AUDIT -->
    <div id="tab-history" class="tab-content hidden space-y-6">
        <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>📜</span>
                        <span>Deployment & Operations History</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Audit log of all deployments, migrations, and operations run on this site.</p>
                </div>
                <button onclick="loadHistory()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition">
                    Refresh Log
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Date / Time</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Commit Range</th>
                            <th class="py-3 px-4">Duration</th>
                            <th class="py-3 px-4">Operator IP</th>
                            <th class="py-3 px-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody" class="divide-y divide-slate-800/60 font-mono text-[11px]">
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 font-sans text-xs">Loading history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: SERVER DIAGNOSTICS & LOGS -->
    <div id="tab-diagnostics" class="tab-content hidden space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Server Diagnostics -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5 space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                    <span>🩺</span>
                    <span>Shared Hosting Environment Diagnostics</span>
                </h3>
                <div id="diag-container" class="space-y-2.5 text-xs">
                    <div class="text-slate-500">Inspecting server environment...</div>
                </div>
            </div>

            <!-- Laravel Error Log -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-5 space-y-4 flex flex-col">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <span>📋</span>
                        <span>Latest Laravel Log Entries</span>
                    </h3>
                    <button onclick="loadLaravelLogs()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] rounded transition">
                        Refresh Log
                    </button>
                </div>
                <div id="laravel-logs-container" class="flex-1 bg-black/60 p-3 rounded-lg border border-slate-800 text-[11px] font-mono text-slate-300 max-h-80 overflow-y-auto whitespace-pre-wrap">
Loading logs...
                </div>
            </div>
        </div>
    </div>

    <!-- LIVE OPERATION TERMINAL / OUTPUT CONSOLE (Always Visible at bottom) -->
    <div class="terminal-window shadow-2xl overflow-hidden flex flex-col">
        <!-- Terminal Header -->
        <div class="bg-slate-900 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2">
                <div class="flex space-x-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                </div>
                <span class="text-slate-400 font-mono ml-2 text-[11px]">Deployer Console & Live Logs</span>
                <span id="terminal-spinner" class="hidden text-sky-400 spin">⟳</span>
            </div>

            <div class="flex items-center space-x-2 text-[11px]">
                <button onclick="copyTerminalOutput()" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    📋 Copy Log
                </button>
                <button onclick="clearTerminal()" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    Clear Console
                </button>
            </div>
        </div>

        <!-- Terminal Output Area -->
        <div id="terminal-body" class="p-4 mono text-[12px] text-slate-200 h-64 overflow-y-auto leading-relaxed whitespace-pre-wrap select-text">
<span class="text-sky-400 font-bold">🚀 Advanced Web Deployment & Operations Manager Initialized.</span>
<span class="text-slate-500">Ready to execute actions. Choose an operation above or click "Deploy Now".</span>
        </div>
    </div>

</main>

<footer class="border-t border-slate-800/60 py-4 text-center text-[11px] text-slate-500">
    <span><?= htmlspecialchars($envConfig['APP_NAME'] ?? 'Laravel') ?> • Web Deployer v<?= DEPLOYER_VERSION ?> • Running on PHP <?= PHP_VERSION ?></span>
</footer>

<!-- JAVASCRIPT APP LOGIC -->
<script>
const CSRF_TOKEN = <?= json_encode($_SESSION['deployer_csrf'] ?? '') ?>;
let isBusy = false;

// Tab Switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        btn.classList.add('active');
        const tabId = btn.getAttribute('data-tab');
        document.getElementById(tabId).classList.remove('hidden');

        if (tabId === 'tab-changes') checkRemoteUpdates(false);
        if (tabId === 'tab-rollback') loadRecentCommitsForRollback();
        if (tabId === 'tab-history') loadHistory();
        if (tabId === 'tab-diagnostics') { loadDiagnostics(); loadLaravelLogs(); }
    });
});

// Terminal Logging Helper
function logToTerminal(title, content, type = 'info') {
    const term = document.getElementById('terminal-body');
    const time = new Date().toLocaleTimeString();
    
    let colorClass = 'text-slate-300';
    if (type === 'success') colorClass = 'text-emerald-400';
    if (type === 'error') colorClass = 'text-rose-400 font-bold';
    if (type === 'warn') colorClass = 'text-amber-400';
    if (type === 'command') colorClass = 'text-sky-400 font-semibold';

    const header = `<div class="mt-3 pt-2 border-t border-slate-800/80 text-[11px] text-slate-500 font-mono">[${time}] ${title}</div>`;
    const body = `<div class="${colorClass}">${escapeHtml(content)}</div>`;
    term.innerHTML += header + body;
    term.scrollTop = term.scrollHeight;
}

function clearTerminal() {
    document.getElementById('terminal-body').innerHTML = '<span class="text-slate-500 font-mono">// Console cleared.</span>';
}

function copyTerminalOutput() {
    const text = document.getElementById('terminal-body').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Console logs copied to clipboard!');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function setBusy(state) {
    isBusy = state;
    const spinner = document.getElementById('terminal-spinner');
    const deployBtn = document.getElementById('btn-full-deploy');
    if (spinner) spinner.classList.toggle('hidden', !state);
    if (deployBtn) {
        deployBtn.disabled = state;
        deployBtn.classList.toggle('opacity-50', state);
    }
}

// API Post Helper
async function postAction(action, data = {}) {
    setBusy(true);
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', CSRF_TOKEN);
    for (const [key, val] of Object.entries(data)) {
        formData.append(key, val);
    }

    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        const json = await response.json();
        setBusy(false);
        return json;
    } catch (err) {
        setBusy(false);
        return { success: false, error: 'Network / Server Error: ' + err.message };
    }
}

// Refresh Top Status Bar
async function refreshStatus() {
    const icon = document.getElementById('refresh-icon');
    if (icon) icon.classList.add('spin');

    const res = await postAction('get_status');
    if (icon) icon.classList.remove('spin');

    if (res.success && res.data) {
        const d = res.data;
        document.getElementById('header-branch').innerText = d.branch;
        document.getElementById('header-commit').innerText = d.commit.short + ' - ' + d.commit.subject;
        
        // DB status
        const dbPill = document.getElementById('status-db');
        if (d.db_ok) {
            dbPill.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400"></span><span class="text-[11px] text-emerald-300">DB: Connected</span>`;
        } else {
            dbPill.innerHTML = `<span class="w-2 h-2 rounded-full bg-rose-400"></span><span class="text-[11px] text-rose-300" title="${escapeHtml(d.db_message)}">DB: Error</span>`;
        }

        // Maintenance status
        updateMaintUi(d.maintenance);
    }
}

function updateMaintUi(isDown) {
    const indicator = document.getElementById('maint-indicator');
    const text = document.getElementById('maint-text');
    const cardStatus = document.getElementById('card-maint-status');

    if (isDown) {
        indicator.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
        text.innerText = 'App: Down (503)';
        if (cardStatus) {
            cardStatus.className = 'text-[10px] text-amber-400 mono';
            cardStatus.innerText = 'MAINTENANCE';
        }
    } else {
        indicator.className = 'w-2 h-2 rounded-full bg-emerald-400';
        text.innerText = 'App: Live';
        if (cardStatus) {
            cardStatus.className = 'text-[10px] text-emerald-400 mono';
            cardStatus.innerText = 'LIVE';
        }
    }
}

// Generic Run Action Handler
async function runAction(action, extraData = {}) {
    if (isBusy) {
        alert('Another deployment operation is currently in progress. Please wait.');
        return;
    }
    logToTerminal(`Executing action: ${action}...`, 'Starting...', 'command');
    const res = await postAction(action, extraData);
    
    if (res.success) {
        logToTerminal(`Action Completed: ${action} (${res.duration || 0}s)`, res.output || res.message || 'Success', 'success');
        refreshStatus();
    } else {
        logToTerminal(`Action Failed: ${action}`, res.error || res.output || 'Unknown error occurred', 'error');
    }
}

// Full Automated Deploy
async function triggerFullDeploy() {
    if (isBusy) return;
    if (!confirm('Start Full Automated Deployment now? This will pull latest code and run migrations.')) {
        return;
    }

    const branch = document.getElementById('deploy-branch').value.trim() || 'main';
    const withMaint = document.getElementById('deploy-maint').checked ? '1' : '';
    const withCache = document.getElementById('deploy-cache').checked ? '1' : '';

    logToTerminal('DEPLOY PIPELINE', `Triggering pipeline for branch '${branch}'...`, 'command');
    
    const res = await postAction('full_deploy', {
        branch: branch,
        with_maintenance: withMaint,
        with_cache: withCache
    });

    if (res.success) {
        logToTerminal('DEPLOY PIPELINE COMPLETED', res.output, 'success');
        refreshStatus();
    } else {
        logToTerminal('DEPLOY PIPELINE FAILED', res.output || res.error, 'error');
    }
}

// Maintenance Mode Toggle
async function toggleMaintenance() {
    const res = await postAction('maintenance_toggle');
    if (res.success) {
        logToTerminal('Maintenance Mode', res.message || res.output, 'warn');
        refreshStatus();
    } else {
        logToTerminal('Maintenance Error', res.error, 'error');
    }
}

// Check Remote Updates & Incoming Commits
async function checkRemoteUpdates(verbose = true) {
    const summary = document.getElementById('changes-summary');
    summary.innerHTML = '<div class="py-6 text-center text-xs text-sky-400">Fetching remote repository (git fetch origin)...</div>';

    const branch = document.getElementById('deploy-branch')?.value.trim() || 'main';
    const res = await postAction('check_updates', { branch: branch });

    if (!res.success) {
        summary.innerHTML = `<div class="p-4 bg-rose-950/60 border border-rose-800 rounded-xl text-xs text-rose-300">
            <strong>Fetch Failed:</strong> ${escapeHtml(res.error || 'Unable to fetch remote branch')}
        </div>`;
        return;
    }

    const d = res.data;
    const badge = document.getElementById('badge-incoming');
    if (badge) {
        badge.innerText = d.incoming_count;
        badge.classList.toggle('hidden', d.incoming_count === 0);
    }

    let html = '';

    // Status Banner
    if (d.incoming_count === 0) {
        html += `<div class="p-4 bg-emerald-950/40 border border-emerald-800/60 rounded-xl text-xs text-emerald-300 flex items-center justify-between">
            <span class="flex items-center space-x-2">
                <span>✅</span>
                <span>Your local branch is completely up-to-date with <strong>origin/${escapeHtml(d.branch)}</strong>. No new commits found.</span>
            </span>
            <span class="font-mono text-[11px] text-emerald-400">0 pending</span>
        </div>`;
    } else {
        html += `<div class="p-4 bg-sky-950/50 border border-sky-800 rounded-xl text-xs text-sky-200 flex items-center justify-between">
            <div>
                <span class="font-bold text-sky-300 text-sm">🎉 ${d.incoming_count} New Commit(s) Ready to Pull!</span>
                <p class="text-[11px] text-slate-300 mt-0.5">These changes have been committed to GitHub/remote and will be pulled upon deployment.</p>
            </div>
            <button onclick="triggerFullDeploy()" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-lg text-xs shadow-lg transition">
                🚀 Deploy Changes Now
            </button>
        </div>`;

        // Commits List
        html += `<div class="space-y-2 mt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Incoming Commits:</h4>
            <div class="divide-y divide-slate-800 border border-slate-800 rounded-xl bg-slate-950/60 overflow-hidden">`;

        d.incoming_commits.forEach(c => {
            html += `<div class="p-3 text-xs flex items-center justify-between hover:bg-slate-900/50 transition">
                <div class="space-y-0.5">
                    <div class="text-white font-medium">${escapeHtml(c.subject)}</div>
                    <div class="text-[11px] text-slate-400">By <strong>${escapeHtml(c.author)}</strong> • ${escapeHtml(c.date)}</div>
                </div>
                <span class="mono text-[11px] bg-slate-800 px-2 py-0.5 rounded text-sky-400">${escapeHtml(c.short)}</span>
            </div>`;
        });

        html += `</div></div>`;
    }

    // Changed files diff stat
    if (d.diff_stat) {
        html += `<div class="mt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Files Changed Summary:</h4>
            <pre class="bg-black/50 p-3 rounded-xl border border-slate-800 text-[11px] mono text-slate-300 overflow-x-auto">${escapeHtml(d.diff_stat)}</pre>
        </div>`;
    }

    // Local uncommitted files warning
    if (d.has_local_changes) {
        html += `<div class="mt-4 p-4 bg-amber-950/40 border border-amber-800/60 rounded-xl text-xs text-amber-300">
            <div class="flex items-center justify-between mb-2">
                <strong>⚠️ Warning: Local Uncommitted Files Detected</strong>
                <button onclick="runAction('git_stash')" class="px-2.5 py-1 bg-amber-700 hover:bg-amber-600 text-white rounded text-[11px] font-semibold transition">
                    Stash Local Changes
                </button>
            </div>
            <p class="text-[11px] text-slate-300 mb-2">If pulling fails with merge conflicts, use "Git Stash" to stash these files before deploying:</p>
            <pre class="bg-black/40 p-2.5 rounded border border-amber-900/40 text-[11px] font-mono text-amber-200">${escapeHtml(d.local_changes)}</pre>
        </div>`;
    }

    summary.innerHTML = html;
    if (verbose) logToTerminal('Remote Updates Check', `Found ${d.incoming_count} pending commit(s).`, 'info');
}

// Load Recent Commits for Rollback Dropdown
async function loadRecentCommitsForRollback() {
    const select = document.getElementById('rollback-commit-select');
    select.innerHTML = '<option value="">Loading recent commits...</option>';

    const res = await postAction('git_log');
    if (res.success && res.commits) {
        let options = '';
        res.commits.forEach((c, idx) => {
            const prefix = (idx === 0) ? '(Current HEAD) ' : '';
            options += `<option value="${c.hash}" ${idx === 1 ? 'selected' : ''}>
                ${prefix}${c.short} — ${c.subject.substring(0, 45)} (${c.date})
            </option>`;
        });
        select.innerHTML = options;
    }
}

// Trigger Migration Rollback
async function triggerMigrateRollback() {
    const steps = document.getElementById('rollback-steps').value;
    if (!confirm(`Are you sure you want to rollback ${steps} database migration batch(es)?`)) {
        return;
    }
    await runAction('migrate_rollback', { step: steps });
}

// Trigger Git Rollback
async function triggerGitRollback() {
    const select = document.getElementById('rollback-commit-select');
    const commit = select.value;
    if (!commit) {
        alert('Please select a valid commit to rollback to.');
        return;
    }
    const text = select.options[select.selectedIndex].text;
    if (!confirm(`CRITICAL WARNING: This will reset all code files to:\n\n${text}\n\nAre you sure you want to proceed?`)) {
        return;
    }
    await runAction('git_rollback', { target_commit: commit });
}

// Load Deployment History
async function loadHistory() {
    const tbody = document.getElementById('history-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-500 font-sans text-xs">Loading history...</td></tr>';

    const res = await postAction('get_history');
    if (res.success && res.history) {
        if (res.history.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-500 font-sans text-xs">No deployments logged yet.</td></tr>';
            return;
        }

        let rows = '';
        res.history.forEach((h, index) => {
            const isSuccess = (h.status === 'success');
            const statusBadge = isSuccess
                ? '<span class="px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px]">Success</span>'
                : '<span class="px-2 py-0.5 rounded-full bg-rose-950 text-rose-400 border border-rose-800 text-[10px]">Failed</span>';

            const commitRange = (h.commit_before && h.commit_after)
                ? `${h.commit_before} → ${h.commit_after}`
                : (h.commit_before || '—');

            rows += `
                <tr class="hover:bg-slate-900/40 transition">
                    <td class="py-3 px-4 font-sans text-slate-300">${escapeHtml(h.datetime)}</td>
                    <td class="py-3 px-4 font-semibold text-white font-sans">${escapeHtml(h.action)}</td>
                    <td class="py-3 px-4 font-sans">${statusBadge}</td>
                    <td class="py-3 px-4 text-sky-400">${escapeHtml(commitRange)}</td>
                    <td class="py-3 px-4 text-slate-400">${h.duration || 0}s</td>
                    <td class="py-3 px-4 text-slate-400 font-sans">${escapeHtml(h.ip || '—')}</td>
                    <td class="py-3 px-4 text-right font-sans">
                        <button onclick="showHistoryDetail(${index})" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px]">
                            View Log
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = rows;
        window.deployHistoryData = res.history;
    }
}

function showHistoryDetail(index) {
    if (!window.deployHistoryData || !window.deployHistoryData[index]) return;
    const item = window.deployHistoryData[index];
    logToTerminal(`AUDIT LOG [${item.action} at ${item.datetime}]`, item.output, item.status === 'success' ? 'success' : 'error');
}

// Load Diagnostics
async function loadDiagnostics() {
    const container = document.getElementById('diag-container');
    const res = await postAction('get_diagnostics');

    if (res.success && res.data) {
        const d = res.data;
        const checkIcon = '<span class="text-emerald-400 font-bold">✓ Ready</span>';
        const failIcon = '<span class="text-rose-400 font-bold">✗ Blocked</span>';

        container.innerHTML = `
            <div class="p-3 bg-slate-950/80 rounded-lg border border-slate-800 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-400">PHP Version:</span>
                    <strong class="text-white">${escapeHtml(d.php_version)}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Server Software:</span>
                    <span class="text-slate-200">${escapeHtml(d.server_software)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Git Binary:</span>
                    <span class="text-slate-200 font-mono">${escapeHtml(d.git_version)}</span>
                </div>
            </div>

            <div class="p-3 bg-slate-950/80 rounded-lg border border-slate-800 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-400">storage/ Directory:</span>
                    ${d.storage_writable ? checkIcon : failIcon}
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">bootstrap/cache/ Directory:</span>
                    ${d.cache_writable ? checkIcon : failIcon}
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">.git Repository Folder:</span>
                    ${d.git_exists ? checkIcon : failIcon}
                </div>
            </div>

            <div class="p-3 bg-slate-950/80 rounded-lg border border-slate-800 space-y-1.5">
                <div class="text-xs font-semibold text-slate-300 mb-1">Process Execution Support:</div>
                <div class="flex justify-between">
                    <span class="text-slate-400">proc_open():</span>
                    ${d.proc_open_available ? checkIcon : failIcon}
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">exec():</span>
                    ${d.exec_available ? checkIcon : failIcon}
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">shell_exec():</span>
                    ${d.shell_exec_available ? checkIcon : failIcon}
                </div>
            </div>

            ${d.disabled_functions.length > 0 ? `
                <div class="p-3 bg-slate-950/80 rounded-lg border border-slate-800">
                    <span class="text-slate-400 block mb-1">Disabled PHP Functions (php.ini):</span>
                    <span class="text-amber-300 font-mono text-[11px] break-all">${escapeHtml(d.disabled_functions.join(', '))}</span>
                </div>
            ` : ''}
        `;
    }
}

// Load Laravel Logs
async function loadLaravelLogs() {
    const container = document.getElementById('laravel-logs-container');
    container.innerText = 'Fetching latest log lines...';
    const res = await postAction('get_laravel_logs');
    if (res.success) {
        container.innerText = res.output || 'Log is empty.';
    } else {
        container.innerText = res.error || 'Failed to read laravel.log';
    }
}

// Initial Boot
window.addEventListener('DOMContentLoaded', () => {
    refreshStatus();
});
</script>

<?php } ?>
</body>
</html>
