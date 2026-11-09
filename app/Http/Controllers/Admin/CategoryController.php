<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display categories and their navigation menu status.
     */
    public function index(): View
    {
        $rootCategories = Category::roots()
            ->withCount('articles')
            ->with(['children' => fn ($q) => $q->withCount('articles')->orderBy('display_order')])
            ->orderBy('display_order')
            ->get();

        $allCategories = Category::with('parent')
            ->withCount('articles')
            ->orderBy('parent_id')
            ->orderBy('display_order')
            ->get();

        $menuItemCount = Category::where('show_in_menu', true)->where('is_active', true)->count();
        $totalCategories = Category::count();

        return view('admin.categories.index', compact(
            'rootCategories',
            'allCategories',
            'menuItemCount',
            'totalCategories'
        ));
    }

    /**
     * Store a newly created category and configure its menu visibility.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:categories,slug'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'show_in_menu' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? (Category::max('display_order') + 1),
            'show_in_menu' => $request->boolean('show_in_menu', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$validated['name']}' created successfully and added to navigation configuration.");
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'unique:categories,slug,'.$category->id],
            'parent_id' => ['nullable', 'exists:categories,id', 'not_in:'.$category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['required', 'integer', 'min:0'],
            'show_in_menu' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'],
            'show_in_menu' => $request->boolean('show_in_menu'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Quick toggle for navigation menu inclusion.
     */
    public function toggleMenu(Category $category): RedirectResponse
    {
        $category->show_in_menu = ! $category->show_in_menu;
        $category->save();

        $status = $category->show_in_menu ? 'included in' : 'removed from';

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been {$status} the navigation menu.");
    }

    /**
     * Remove the specified category from database.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', "Cannot delete category '{$category->name}' because it contains articles. Reassign or remove its articles first.");
        }

        if ($category->children()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', "Cannot delete category '{$category->name}' because it has subcategories. Reassign or remove subcategories first.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$name}' deleted successfully.");
    }
}
