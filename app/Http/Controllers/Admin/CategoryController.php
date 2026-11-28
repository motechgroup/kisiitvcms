<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Bulk-save inline edits to navigation order, category names, menu visibility, and active status.
     */
    public function bulkSave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.display_order' => ['required', 'integer', 'min:0'],
            'items.*.show_in_menu' => ['nullable', 'boolean'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);

        $updatedCount = 0;

        DB::transaction(function () use ($validated, &$updatedCount): void {
            $categories = Category::whereIn('id', array_keys($validated['items']))->get()->keyBy('id');

            foreach ($validated['items'] as $categoryId => $data) {
                $category = $categories->get((int) $categoryId);
                if (! $category) {
                    continue;
                }

                $category->update([
                    'name' => trim((string) $data['name']),
                    'display_order' => (int) $data['display_order'],
                    'show_in_menu' => filter_var($data['show_in_menu'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_active' => filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);

                $updatedCount++;
            }
        });

        Cache::forget('nav_categories_menu_v2');

        return redirect()->route('admin.categories.index')
            ->with('success', "Saved navigation and category changes ({$updatedCount} categories updated).");
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
     * Quick toggle for category active status.
     */
    public function toggleActive(Category $category): RedirectResponse
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        $status = $category->is_active ? 'activated' : 'disabled';

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been {$status}.");
    }

    /**
     * Move a category up or down in the navigation display order.
     */
    public function move(Request $request, Category $category): RedirectResponse
    {
        $direction = $request->input('direction', 'up');

        $siblings = Category::where('parent_id', $category->parent_id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->values();

        $currentIndex = $siblings->search(fn (Category $item) => $item->id === $category->id);

        if ($currentIndex !== false) {
            $targetIndex = $direction === 'down' ? $currentIndex + 1 : $currentIndex - 1;

            if ($siblings->has($targetIndex)) {
                $reordered = $siblings->all();
                [$reordered[$currentIndex], $reordered[$targetIndex]] = [$reordered[$targetIndex], $reordered[$currentIndex]];

                foreach ($reordered as $order => $item) {
                    $item->update(['display_order' => $order + 1]);
                }
            }
        }

        return redirect()->route('admin.categories.index')
            ->with('success', "Navigation order updated for '{$category->name}'.");
    }

    /**
     * Safely delete a category (reassigning any articles and preserving subcategories).
     */
    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $articleCount = $category->articles()->count();

        if ($articleCount > 0) {
            $fallback = $category->parent_id
                ? Category::find($category->parent_id)
                : Category::where('id', '!=', $category->id)
                    ->whereNull('parent_id')
                    ->orderBy('display_order')
                    ->first()
                    ?? Category::where('id', '!=', $category->id)->first();

            if (! $fallback) {
                return redirect()->route('admin.categories.index')
                    ->with('error', "Cannot delete '{$name}' because it is the only category left and still has {$articleCount} articles.");
            }
        }

        DB::transaction(function () use ($category, $articleCount): void {
            if ($articleCount > 0) {
                $fallbackId = $category->parent_id
                    ?: Category::where('id', '!=', $category->id)
                        ->whereNull('parent_id')
                        ->orderBy('display_order')
                        ->value('id')
                    ?: Category::where('id', '!=', $category->id)->value('id');

                Article::where('category_id', $category->id)->update(['category_id' => $fallbackId]);
            }

            Article::where('subcategory_id', $category->id)->update(['subcategory_id' => null]);
            Category::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);

            $category->delete();
        });

        $note = $articleCount > 0 ? " ({$articleCount} articles were safely reassigned)." : '.';

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$name}' deleted successfully{$note}");
    }
}
