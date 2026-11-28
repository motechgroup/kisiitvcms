@extends('layouts.admin')

@section('title', 'Categories & Navigation Menu')
@section('page_title', 'Navigation Menu & News Categories')

@section('content')
<div class="space-y-6" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    hasUnsavedChanges: false,
    activeEditCategory: null,
    openEdit(cat) {
        this.activeEditCategory = {
            id: cat.id,
            original_slug: cat.slug,
            name: cat.name,
            slug: cat.slug,
            parent_id: cat.parent_id ? String(cat.parent_id) : '',
            display_order: cat.display_order ?? 0,
            description: cat.description ?? '',
            show_in_menu: Boolean(cat.show_in_menu),
            is_active: Boolean(cat.is_active)
        };
        this.editModalOpen = true;
    }
}">

    <!-- Standalone Bulk Save Form (inputs below bind via form='bulk-categories-form' to avoid nested forms) -->
    <form id="bulk-categories-form" method="POST" action="{{ route('admin.categories.bulk-save') }}">
        @csrf
        @method('PUT')
    </form>

    <!-- Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Editorial Navigation &amp; Categories</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Edit category names, reorder navigation items, toggle <strong class="text-emerald-700">"In Menu"</strong> or <strong class="text-blue-700">"Active"</strong> inline, then click <strong>Save All Changes</strong>.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span x-show="hasUnsavedChanges" x-cloak
                class="px-2.5 py-1 rounded bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-bold">
                Unsaved changes
            </span>
            <button type="submit" form="bulk-categories-form"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5 shadow cursor-pointer">
                <span>✓</span>
                <span>Save All Changes</span>
            </button>
            <button type="button" @click="createModalOpen = true"
                class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5 shadow cursor-pointer">
                <span>+</span>
                <span>Create New Category</span>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Total News Categories</span>
            <span class="text-xl font-black text-slate-900 mt-1 block font-headline">{{ $totalCategories }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Active Navigation Menu Items</span>
            <span class="text-xl font-black text-emerald-600 mt-1 block font-headline">{{ $menuItemCount }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Root News Desks</span>
            <span class="text-xl font-black text-[#1b52bc] mt-1 block font-headline">{{ $rootCategories->count() }}</span>
        </div>
    </div>

    <!-- Live Top Navigation Preview -->
    <div class="bg-[#0a1c3d] text-white px-5 py-3.5 rounded border border-slate-800 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-[10px] font-black uppercase tracking-widest text-amber-300 mr-2">Current Live Header Nav:</span>
            <span class="px-2.5 py-1 rounded bg-white/10 font-bold">Home</span>
            @foreach($rootCategories->where('is_active', true)->where('show_in_menu', true)->sortBy('display_order') as $navItem)
                <span class="px-2.5 py-1 rounded bg-white/15 font-semibold">
                    {{ $navItem->name }}
                    @if($navItem->children->where('is_active', true)->where('show_in_menu', true)->isNotEmpty())
                        <span class="text-[10px] text-slate-300">▾</span>
                    @endif
                </span>
            @endforeach
        </div>
    </div>

    <!-- Categories & Navigation Table -->
    <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                    Configured Categories &amp; Navigation Hierarchy
                </h3>
                <span class="text-[11px] text-slate-500">Adjust Order, Name, In Menu, or Active below and click "Save All Changes".</span>
            </div>
            <button type="submit" form="bulk-categories-form"
                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold uppercase tracking-wider rounded transition cursor-pointer self-start sm:self-auto">
                Save All Changes
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Order</th>
                        <th class="py-3 px-4">Category Name &amp; Slug</th>
                        <th class="py-3 px-4">Hierarchy</th>
                        <th class="py-3 px-4">Articles</th>
                        <th class="py-3 px-4 text-center">In Navigation Menu</th>
                        <th class="py-3 px-4 text-center">Active</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rootCategories as $root)
                        <!-- ROOT CATEGORY ROW -->
                        <tr class="bg-white hover:bg-slate-50/80 transition font-semibold">
                            <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <input type="number"
                                        form="bulk-categories-form"
                                        name="items[{{ $root->id }}][display_order]"
                                        value="{{ $root->display_order }}"
                                        min="0"
                                        @input="hasUnsavedChanges = true"
                                        class="w-16 px-2 py-1 text-xs font-mono border border-slate-300 rounded bg-white text-slate-800 focus:border-[#1b52bc] outline-none">
                                    <form method="POST" action="{{ route('admin.categories.move', $root) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" title="Move Up" class="px-1.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer">&uarr;</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.categories.move', $root) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" title="Move Down" class="px-1.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer">&darr;</button>
                                    </form>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#1b52bc] shrink-0"></span>
                                    <input type="text"
                                        form="bulk-categories-form"
                                        name="items[{{ $root->id }}][name]"
                                        value="{{ $root->name }}"
                                        required
                                        @input="hasUnsavedChanges = true"
                                        class="px-2.5 py-1 text-xs font-bold text-slate-900 border border-slate-200 hover:border-slate-300 focus:border-[#1b52bc] rounded bg-white outline-none w-44">
                                    <span class="text-[10px] text-slate-400 font-mono">/{{ $root->slug }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="bg-blue-50 text-[#1b52bc] font-bold text-[10px] uppercase px-2 py-0.5 rounded border border-blue-200">
                                    Root ({{ $root->children->count() }} sub)
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-700">
                                {{ $root->articles_count }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="hidden" form="bulk-categories-form" name="items[{{ $root->id }}][show_in_menu]" value="0">
                                <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                    <input type="checkbox"
                                        form="bulk-categories-form"
                                        name="items[{{ $root->id }}][show_in_menu]"
                                        value="1"
                                        {{ $root->show_in_menu ? 'checked' : '' }}
                                        @change="hasUnsavedChanges = true"
                                        class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                    <span class="text-[10px] font-bold uppercase {{ $root->show_in_menu ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ $root->show_in_menu ? 'In Menu' : 'Hidden' }}
                                    </span>
                                </label>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="hidden" form="bulk-categories-form" name="items[{{ $root->id }}][is_active]" value="0">
                                <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                    <input type="checkbox"
                                        form="bulk-categories-form"
                                        name="items[{{ $root->id }}][is_active]"
                                        value="1"
                                        {{ $root->is_active ? 'checked' : '' }}
                                        @change="hasUnsavedChanges = true"
                                        class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4 cursor-pointer">
                                    <span class="text-[10px] font-bold uppercase {{ $root->is_active ? 'text-blue-700' : 'text-slate-400' }}">
                                        {{ $root->is_active ? 'Active' : 'Off' }}
                                    </span>
                                </label>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                <button type="button"
                                    @click="openEdit({{ Js::from($root) }})"
                                    class="text-blue-600 hover:underline font-semibold cursor-pointer">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $root) }}" class="inline"
                                    onsubmit="return confirm('Delete category {{ addslashes($root->name) }}? Any articles in it will be safely reassigned to another category.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline font-semibold cursor-pointer">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <!-- SUBCATEGORIES ROWS -->
                        @foreach($root->children as $child)
                            <tr class="bg-slate-50/40 hover:bg-slate-100/60 transition text-slate-600 text-xs">
                                <td class="py-2.5 px-4 font-mono text-slate-400 pl-8 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <span>↳</span>
                                        <input type="number"
                                            form="bulk-categories-form"
                                            name="items[{{ $child->id }}][display_order]"
                                            value="{{ $child->display_order }}"
                                            min="0"
                                            @input="hasUnsavedChanges = true"
                                            class="w-14 px-1.5 py-0.5 text-xs font-mono border border-slate-300 rounded bg-white text-slate-700 focus:border-[#1b52bc] outline-none">
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 pl-8">
                                    <div class="flex items-center space-x-2">
                                        <input type="text"
                                            form="bulk-categories-form"
                                            name="items[{{ $child->id }}][name]"
                                            value="{{ $child->name }}"
                                            required
                                            @input="hasUnsavedChanges = true"
                                            class="px-2 py-0.5 text-xs font-semibold text-slate-800 border border-slate-200 hover:border-slate-300 focus:border-[#1b52bc] rounded bg-white outline-none w-40">
                                        <span class="text-[10px] text-slate-400 font-mono">/{{ $child->slug }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="text-slate-500 text-[11px]">Sub of {{ $root->name }}</span>
                                </td>
                                <td class="py-2.5 px-4 font-mono text-slate-500">
                                    {{ $child->articles_count }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <input type="hidden" form="bulk-categories-form" name="items[{{ $child->id }}][show_in_menu]" value="0">
                                    <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                        <input type="checkbox"
                                            form="bulk-categories-form"
                                            name="items[{{ $child->id }}][show_in_menu]"
                                            value="1"
                                            {{ $child->show_in_menu ? 'checked' : '' }}
                                            @change="hasUnsavedChanges = true"
                                            class="rounded text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5 cursor-pointer">
                                        <span class="text-[9px] font-bold uppercase {{ $child->show_in_menu ? 'text-emerald-700' : 'text-slate-400' }}">
                                            {{ $child->show_in_menu ? 'Dropdown' : 'Hidden' }}
                                        </span>
                                    </label>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <input type="hidden" form="bulk-categories-form" name="items[{{ $child->id }}][is_active]" value="0">
                                    <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                        <input type="checkbox"
                                            form="bulk-categories-form"
                                            name="items[{{ $child->id }}][is_active]"
                                            value="1"
                                            {{ $child->is_active ? 'checked' : '' }}
                                            @change="hasUnsavedChanges = true"
                                            class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-3.5 h-3.5 cursor-pointer">
                                        <span class="text-[9px] font-bold uppercase {{ $child->is_active ? 'text-blue-700' : 'text-slate-400' }}">
                                            {{ $child->is_active ? 'Active' : 'Off' }}
                                        </span>
                                    </label>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-2 whitespace-nowrap">
                                    <button type="button"
                                        @click="openEdit({{ Js::from($child) }})"
                                        class="text-blue-600 hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" class="inline"
                                        onsubmit="return confirm('Delete subcategory {{ addslashes($child->name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline cursor-pointer">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No categories found. Create a category to build your menu items.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Bottom Save Bar -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-4">
            <span class="text-[11px] text-slate-500">
                Tip: Changes made inline to Order, Name, In Navigation Menu, or Active take effect on the live site immediately after clicking Save.
            </span>
            <button type="submit" form="bulk-categories-form"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded transition shadow cursor-pointer shrink-0">
                ✓ Save All Changes
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CREATE CATEGORY & MENU ITEM MODAL          -->
    <!-- ========================================== -->
    <div x-show="createModalOpen" x-cloak style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded shadow-2xl max-w-lg w-full p-6 border-t-4 border-[#1b52bc]" @click.outside="createModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <h3 class="text-base font-bold text-slate-900 font-headline">Create Category &amp; Navigation Menu Item</h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Investigations, Climate, Agribusiness"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Parent Category (Optional)</label>
                    <select name="parent_id" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none bg-white">
                        <option value="">-- None (Top Level Menu Item) --</option>
                        @foreach($rootCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Selecting none makes this a top-level section in the navigation bar.</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Display Order</label>
                        <input type="number" name="display_order" value="{{ $rootCategories->count() + 1 }}" min="0"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Custom Slug (Optional)</label>
                        <input type="text" name="slug" placeholder="Auto-generated"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Editorial Description</label>
                    <textarea name="description" rows="2" placeholder="Brief mission statement for this news category..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none"></textarea>
                </div>

                <!-- Show in Menu Checkbox -->
                <div class="p-3 bg-blue-50 border border-blue-200 rounded space-y-2">
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="hidden" name="show_in_menu" value="0">
                        <input type="checkbox" name="show_in_menu" value="1" checked
                            class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                        <span class="text-xs font-bold text-blue-950">Show in Public Navigation Menu</span>
                    </label>
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked
                            class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                        <span class="text-xs font-bold text-blue-950">Active Category</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 border border-slate-300 text-xs font-semibold rounded hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition cursor-pointer">
                        Save Category &amp; Add to Navigation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- EDIT CATEGORY MODAL                        -->
    <!-- ========================================== -->
    <div x-show="editModalOpen" x-cloak style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded shadow-2xl max-w-lg w-full p-6 border-t-4 border-[#1b52bc]" @click.outside="editModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <h3 class="text-base font-bold text-slate-900 font-headline">Edit Category &amp; Menu Settings</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <template x-if="activeEditCategory">
                <form :action="'/admin/categories/' + activeEditCategory.original_slug" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Category Name *</label>
                        <input type="text" name="name" x-model="activeEditCategory.name" required
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Slug *</label>
                        <input type="text" name="slug" x-model="activeEditCategory.slug" required
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Parent Category</label>
                        <select name="parent_id" x-model="activeEditCategory.parent_id" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none bg-white">
                            <option value="">-- None (Root Section) --</option>
                            @foreach($rootCategories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Display Order</label>
                        <input type="number" name="display_order" x-model="activeEditCategory.display_order" required min="0"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                        <textarea name="description" rows="2" x-model="activeEditCategory.description"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:outline-none"></textarea>
                    </div>

                    <!-- Visibility Checkboxes -->
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded space-y-2">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="hidden" name="show_in_menu" value="0">
                            <input type="checkbox" name="show_in_menu" value="1" x-model="activeEditCategory.show_in_menu"
                                class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                            <span class="text-xs font-bold text-blue-950">Show in Public Navigation Menu</span>
                        </label>
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="activeEditCategory.is_active"
                                class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                            <span class="text-xs font-bold text-blue-950">Active Category</span>
                        </label>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-3">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-slate-300 text-xs font-semibold rounded hover:bg-slate-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition cursor-pointer">
                            Save Changes
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
