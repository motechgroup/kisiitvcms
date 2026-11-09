<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicCategoryController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Public Editorial Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public SEO, Sitemap & Syndication Feeds
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/feed', [SeoController::class, 'rss'])->name('seo.rss');
Route::get('/rss.xml', [SeoController::class, 'rss']);

// Public Article & Category Routes
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/category/{category}', [PublicCategoryController::class, 'show'])->name('categories.show');

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

        // Staff Profile Settings & Account Information
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});
