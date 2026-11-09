<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\Article;
use App\Models\Category;
use Illuminate\View\View;

class PublicCategoryController extends Controller
{
    /**
     * Display a category archive / section wire.
     */
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        // Include subcategories in query
        $categoryIds = Category::where('id', $category->id)
            ->orWhere('parent_id', $category->id)
            ->pluck('id');

        $articles = Article::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12);

        $subcategories = $category->children()->where('is_active', true)->get();

        // Navigation Categories
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        // Trending within or across
        $mostRead = Article::published()
            ->with(['category', 'author'])
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        // Sidebar Ad
        $sidebarAdPlacement = AdPlacement::where('slug', 'sidebar-mrec')->first();
        $sidebarAd = $sidebarAdPlacement?->advertisements()->active()->inRandomOrder()->first();

        return view('categories.show', compact(
            'category',
            'articles',
            'subcategories',
            'navCategories',
            'mostRead',
            'sidebarAd'
        ));
    }
}
