<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationCampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\DonationController as AdminDonationController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\LiveStreamController as AdminLiveStreamController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DonationCampaignController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\PublicCategoryController;
use App\Http\Controllers\SeoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Public Editorial Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public SEO, Sitemap & Syndication Feeds
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/feed', [SeoController::class, 'rss'])->name('seo.rss');
Route::get('/rss.xml', [SeoController::class, 'rss']);
Route::get('/ads.txt', [SeoController::class, 'adsTxt'])->name('seo.ads-txt');

// Public Article & Category Routes
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/category/{category}', [PublicCategoryController::class, 'show'])->name('categories.show');

// Public Live Streaming & Viewer Donations Routes
Route::get('/live', [LiveStreamController::class, 'index'])->name('live.index');
Route::get('/live/{liveStream}', [LiveStreamController::class, 'show'])->name('live.show');
Route::get('/donate', [DonationController::class, 'index'])->name('donations.index');
Route::get('/campaigns', [DonationCampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign:slug}', [DonationCampaignController::class, 'show'])->name('campaigns.show');
Route::get('/donate/campaigns/{campaign:slug}', [DonationCampaignController::class, 'show']);
Route::post('/donations', [DonationController::class, 'store'])->name('donations.store');
Route::get('/donations/{donation}/checkout', [DonationController::class, 'checkout'])->name('donations.checkout');
Route::post('/donations/{donation}/stk-push', [DonationController::class, 'initiateStkPush'])->name('donations.stk-push');
Route::get('/donations/{donation}/status', [DonationController::class, 'status'])->name('donations.status');
Route::get('/donations/callback/{gateway}', [DonationController::class, 'callback'])->name('donations.callback');
Route::post('/donations/{donation}/complete', [DonationController::class, 'complete'])->name('donations.complete');
Route::any('/donations/webhook/{gateway}', [DonationController::class, 'webhook'])->name('donations.webhook');

// Public Events & Digital Ticketing Routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/lookup-attendee', [EventController::class, 'lookupAttendee'])->name('events.lookup-attendee');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/{event:slug}/purchase', [EventController::class, 'purchase'])->name('events.purchase');
Route::get('/events/checkout/callback/{gateway}', [EventController::class, 'callback'])->name('events.checkout.callback');
Route::get('/events/checkout/{order}', [EventController::class, 'checkout'])->name('events.checkout');
Route::post('/events/checkout/{order}/complete', [EventController::class, 'complete'])->name('events.checkout.complete');
Route::get('/tickets/{ticket}', [EventController::class, 'showTicket'])->name('tickets.show');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
});

// Authenticated Newsroom Admin Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Fast Action Routes for Super Admin
        Route::post('/breaking-news', [DashboardController::class, 'storeBreakingNews'])->name('breaking-news.store');
        Route::patch('/breaking-news/{breakingNews}/toggle', [DashboardController::class, 'toggleBreakingNews'])->name('breaking-news.toggle');
        Route::delete('/breaking-news/{breakingNews}', [DashboardController::class, 'destroyBreakingNews'])->name('breaking-news.destroy');
        Route::post('/articles/quick-create', [DashboardController::class, 'quickCreateArticle'])->name('articles.quick-create');
        Route::patch('/articles/{article}/publish', [DashboardController::class, 'publishArticle'])->name('articles.publish');

        // Navigation Menu & Category Management
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle-menu', [CategoryController::class, 'toggleMenu'])->name('categories.toggle-menu');
        Route::patch('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');
        Route::patch('/categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Articles & Editorial Management
        Route::post('/articles/bulk', [AdminArticleController::class, 'bulkAction'])->name('articles.bulk');
        Route::post('/articles/{article}/clone', [AdminArticleController::class, 'clone'])->name('articles.clone');
        Route::patch('/articles/{article}/toggle-status', [AdminArticleController::class, 'toggleStatus'])->name('articles.toggle-status');
        Route::patch('/articles/{article}/toggle-featured', [AdminArticleController::class, 'toggleFeatured'])->name('articles.toggle-featured');
        Route::patch('/articles/{article}/toggle-breaking', [AdminArticleController::class, 'toggleBreaking'])->name('articles.toggle-breaking');
        Route::patch('/articles/{article}/toggle-editors-pick', [AdminArticleController::class, 'toggleEditorsPick'])->name('articles.toggle-editors-pick');
        Route::resource('articles', AdminArticleController::class);

        // Staff & User Management
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::resource('users', UserController::class);

        // Site Settings, Branding & Theme Customizer
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-sms', [SettingController::class, 'testSms'])->name('settings.test-sms');

        // Live Streaming Studio Management (YouTube, Facebook, Twitch)
        Route::get('/streams', [AdminLiveStreamController::class, 'index'])->name('streams.index');
        Route::post('/streams', [AdminLiveStreamController::class, 'store'])->name('streams.store');
        Route::patch('/streams/toggle-global-donations', [AdminLiveStreamController::class, 'toggleGlobalDonations'])->name('streams.toggle-global-donations');
        Route::put('/streams/{liveStream}', [AdminLiveStreamController::class, 'update'])->name('streams.update');
        Route::patch('/streams/{liveStream}/activate-public', [AdminLiveStreamController::class, 'activatePublic'])->name('streams.activate-public');
        Route::patch('/streams/{liveStream}/toggle-status', [AdminLiveStreamController::class, 'toggleStatus'])->name('streams.toggle-status');
        Route::patch('/streams/{liveStream}/toggle-donations', [AdminLiveStreamController::class, 'toggleDonations'])->name('streams.toggle-donations');
        Route::delete('/streams/{liveStream}', [AdminLiveStreamController::class, 'destroy'])->name('streams.destroy');

        // Payment Gateways Configuration (M-Pesa, Paystack, PayPal, Flutterwave)
        Route::get('/payment-gateways', [PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::put('/payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
        Route::patch('/payment-gateways/{paymentGateway}/toggle-active', [PaymentGatewayController::class, 'toggleActive'])->name('payment-gateways.toggle-active');
        Route::patch('/payment-gateways/{paymentGateway}/toggle-mode', [PaymentGatewayController::class, 'toggleMode'])->name('payment-gateways.toggle-mode');

        // Viewer Donations & Transactions Ledger
        Route::get('/donations', [AdminDonationController::class, 'index'])->name('donations.index');
        Route::patch('/donations/{donation}/status', [AdminDonationController::class, 'updateStatus'])->name('donations.update-status');
        Route::delete('/donations/{donation}', [AdminDonationController::class, 'destroy'])->name('donations.destroy');

        // Donation Campaigns Management (Create shareable campaign links)
        Route::patch('/campaigns/{campaign}/toggle-active', [AdminCampaignController::class, 'toggleActive'])->name('campaigns.toggle-active');
        Route::patch('/campaigns/{campaign}/toggle-featured', [AdminCampaignController::class, 'toggleFeatured'])->name('campaigns.toggle-featured');
        Route::resource('campaigns', AdminCampaignController::class);

        // Events & Digital Ticketing Management
        Route::patch('/events/{event}/toggle-published', [AdminEventController::class, 'togglePublished'])->name('events.toggle-published');
        Route::patch('/events/{event}/toggle-featured', [AdminEventController::class, 'toggleFeatured'])->name('events.toggle-featured');
        Route::post('/events/{event}/duplicate', [AdminEventController::class, 'duplicate'])->name('events.duplicate');
        Route::get('/events/{event}/tickets', [AdminEventController::class, 'tickets'])->name('events.tickets');
        Route::patch('/events/tickets/{ticket}/check-in', [AdminEventController::class, 'checkInTicket'])->name('events.tickets.check-in');
        Route::patch('/events/orders/{order}/mark-paid', [AdminEventController::class, 'markOrderPaid'])->name('events.orders.mark-paid');
        Route::post('/events/orders/{order}/resend', [AdminEventController::class, 'resendOrderNotifications'])->name('events.orders.resend');
        Route::resource('events', AdminEventController::class);

        // Staff Profile Settings & Account Information
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

// Browser Deployment Manager (Fallback route if called through Laravel Front Controller)
Route::any('/migrate.php', function () {
    if (file_exists(public_path('migrate.php'))) {
        require_once public_path('migrate.php');
    } elseif (file_exists(base_path('migrate.php'))) {
        require_once base_path('migrate.php');
    }
    exit;
});

Route::any('/migrate', function () {
    if (file_exists(public_path('migrate.php'))) {
        require_once public_path('migrate.php');
    } elseif (file_exists(base_path('migrate.php'))) {
        require_once base_path('migrate.php');
    }
    exit;
});

Route::any('/deploy.php', function () {
    if (file_exists(public_path('deploy.php'))) {
        require_once public_path('deploy.php');
    } elseif (file_exists(base_path('deploy.php'))) {
        require_once base_path('deploy.php');
    }
    exit;
});

Route::any('/deploy', function () {
    if (file_exists(public_path('deploy.php'))) {
        require_once public_path('deploy.php');
    } elseif (file_exists(base_path('deploy.php'))) {
        require_once base_path('deploy.php');
    }
    exit;
});

Route::get('/seed-database', function (Request $request) {
    if ($request->query('key') !== env('DEPLOY_SECRET', 'deployer_admin_secret_key_2026')) {
        abort(403, 'Unauthorized. Please provide valid ?key= parameter.');
    }
    Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = Artisan::output();

    Artisan::call('db:seed', ['--force' => true]);
    $seedOutput = Artisan::output();

    Artisan::call('optimize:clear');

    return response("<pre style='background:#030712;color:#34d399;padding:25px;font-family:monospace;border-radius:12px;border:1px solid #1e293b;line-height:1.6;'>===========================================\n🌱 DATABASE MIGRATIONS & SEEDING COMPLETED\n===========================================\n\n[MIGRATIONS OUTPUT]\n{$migrateOutput}\n\n[SEEDING OUTPUT]\n{$seedOutput}\n\n<a href='/' style='color:#38bdf8;text-decoration:none;font-weight:bold;'>&larr; View Live Site (kisiitv.co.ke)</a></pre>");
});
