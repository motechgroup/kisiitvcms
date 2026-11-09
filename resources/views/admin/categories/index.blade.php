@extends('layouts.admin')

@section('title', 'Categories & Navigation Menu')
@section('page_title', 'Navigation Menu & News Categories')

@section('content')
<div class="space-y-8" x-data="{ createModalOpen: false, editModalOpen: false, activeEditCategory: null }">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Editorial Navigation &amp; Categories</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Categories flagged with <strong class="text-emerald-700">"In Menu"</strong> are automatically rendered in the public top navigation bar.
            </p>
        </div>
        <button type="button" @click="createModalOpen = true"
            class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5 shadow shrink-0">
            <span>+</span>
            <span>Create New Category</span>
        </button>
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

    <!-- Categories & Navigation Table -->
    <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                Configured Categories &amp; Navigation Hierarchy
            </h3>
            <span class="text-[11px] text-slate-500">Root sections appear as top-level links or dropdown parents</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Order</th>
                        <th class="py-3 px-4">Category Name</th>
                        <th class="py-3 px-4">Hierarchy</th>
                        <th class="py-3 px-4">Published Articles</th>
                        <th class="py-3 px-4 text-center">In Navigation Menu</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rootCategories as $root)
                        <!-- ROOT CATEGORY ROW -->
                        <tr class="bg-white hover:bg-slate-50/80 transition font-semibold">
                            <td class="py-3 px-4 font-mono text-slate-400">
                                #{{ $root->display_order }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#1b52bc]"></span>
                                    <span class="text-slate-900 font-bold text-sm">{{ $root->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">/{{ $root->slug }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="bg-blue-50 text-[#1b52bc] font-bold text-[10px] uppercase px-2 py-0.5 rounded border border-blue-200">
                                    Root Section ({{ $root->children->count() }} subcategories)
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-700">
                                {{ $root->articles_count }} stories
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.categories.toggle-menu', $root) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($root->show_in_menu)
                                        <button type="submit" title="Click to remove from navigation menu"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase hover:bg-red-100 hover:text-red-800 transition cursor-pointer">
                                            <span>✓ In Menu</span>
                                        </button>
                                    @else
                                        <button type="submit" title="Click to add to navigation menu"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-slate-100 text-slate-500 text-[10px] font-extrabold uppercase hover:bg-emerald-100 hover:text-emerald-800 transition cursor-pointer">
                                            <span>Hidden</span>
                                        </button>
                                    @endif
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button type="button" 
                                    @click="activeEditCategory = {{ json_encode($root) }}; editModalOpen = true"
                                    class="text-blue-600 hover:underline font-semibold">
                                    Edit
                                </button>
                                @if($root->articles_count === 0 && $root->children->isEmpty())
                                    <form method="POST" action="{{ route('admin.categories.destroy', $root) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline font-semibold">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>

                        <!-- SUBCATEGORIES ROWS -->
                        @foreach($root->children as $child)
                            <tr class="bg-slate-50/40 hover:bg-slate-100/60 transition text-slate-600 text-xs">
                                <td class="py-2.5 px-4 font-mono text-slate-400 pl-8">
                                    ↳ #{{ $child->display_order }}
                                </td>
                                <td class="py-2.5 px-4 pl-8">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-slate-700 font-semibold">{{ $child->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">/{{ $child->slug }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="text-slate-500 text-[11px]">Subcategory of {{ $root->name }}</span>
                                </td>
                                <td class="py-2.5 px-4 font-mono text-slate-500">
                                    {{ $child->articles_count }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.categories.toggle-menu', $child) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($child->show_in_menu)
                                            <button type="submit" title="Click to remove from menu dropdown"
                                                class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[9px] font-bold uppercase hover:bg-red-50 hover:text-red-700 transition">
                                                In Dropdown
                                            </button>
                                        @else
                                            <button type="submit" title="Click to include in menu dropdown"
                                                class="px-2 py-0.5 rounded bg-slate-100 text-slate-400 text-[9px] font-bold uppercase hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                Hidden
                                            </button>
                                        @endif
                                    </form>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-2">
                                    <button type="button" 
                                        @click="activeEditCategory = {{ json_encode($child) }}; editModalOpen = true"
                                        class="text-blue-600 hover:underline">
                                        Edit
                                    </button>
                                    @if($child->articles_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" class="inline" onsubmit="return confirm('Delete this subcategory?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No categories found. Create a category to build your menu items.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CREATE CATEGORY & MENU ITEM MODAL          -->
    <!-- ========================================== -->
    <div x-show="createModalOpen" style="display: none;" 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded shadow-2xl max-w-lg w-full p-6 border-t-4 border-[#1b52bc]" @click.outside="createModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <h3 class="text-base font-bold text-slate-900 font-headline">Create Category &amp; Navigation Menu Item</h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
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
                <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="checkbox" name="show_in_menu" value="1" checked
                            class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                        <span class="text-xs font-bold text-blue-950">Show in Public Navigation Menu</span>
                    </label>
                    <span class="text-[11px] text-blue-800 block mt-1 ml-6">
                        When checked, this category immediately appears in the website top navigation bar (or within the "More" dropdown).
                    </span>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 border border-slate-300 text-xs font-semibold rounded hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                        Save Category &amp; Add to Navigation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- EDIT CATEGORY MODAL                        -->
    <!-- ========================================== -->
    <div x-show="editModalOpen" style="display: none;" 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded shadow-2xl max-w-lg w-full p-6 border-t-4 border-[#1b52bc]" @click.outside="editModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <h3 class="text-base font-bold text-slate-900 font-headline">Edit Category &amp; Menu Settings</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <template x-if="activeEditCategory">
                <form :action="'/admin/categories/' + activeEditCategory.slug" method="POST" class="space-y-4">
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
                            <option :value="null">-- None (Root Section) --</option>
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

                    <!-- Show in Menu Checkbox -->
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="show_in_menu" value="1" :checked="activeEditCategory.show_in_menu"
                                class="rounded text-[#1b52bc] focus:ring-[#1b52bc] w-4 h-4">
                            <span class="text-xs font-bold text-blue-950">Show in Public Navigation Menu</span>
                        </label>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-3">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-slate-300 text-xs font-semibold rounded hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
