@extends('layouts.admin')

@section('title', 'Edit Campaign: ' . $campaign->title . ' — Kisii TV Newsroom')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between pb-2 border-b border-slate-700">
        <div>
            <h1 class="text-xl sm:text-2xl font-black font-headline text-white tracking-tight">
                Edit Campaign: {{ $campaign->title }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                Update fundraising parameters, goal amounts, dates, and campaign story narrative.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('campaigns.show', $campaign->slug) }}" target="_blank"
                class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wider transition border border-slate-700">
                View Public Link
            </a>
            <a href="{{ route('admin.campaigns.index') }}"
                class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wider transition">
                &larr; Back
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-950/90 border-l-4 border-red-500 text-red-100 p-4 rounded-lg text-xs space-y-1">
            <div class="font-bold uppercase tracking-wider text-red-300">Please correct the form errors:</div>
            <ul class="list-disc list-inside text-red-200">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}" class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                Campaign Title *
            </label>
            <input type="text" name="title" value="{{ old('title', $campaign->title) }}" required
                class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">
        </div>

        <!-- Target Amount & Currency -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                    Target Goal Amount *
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-500 font-mono text-xs font-bold">{{ $campaign->currency }}</span>
                    <input type="number" name="target_amount" value="{{ old('target_amount', (float) $campaign->target_amount) }}" min="100" step="any" required
                        class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg pl-14 pr-4 py-2.5 text-sm font-mono font-bold text-white focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                    Currency Code
                </label>
                <input type="text" name="currency" value="{{ old('currency', $campaign->currency) }}"
                    class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm font-mono text-white focus:outline-none">
            </div>
        </div>

        <!-- Cover Image URL -->
        <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                Cover Image URL (Optional)
            </label>
            <input type="url" name="cover_image" value="{{ old('cover_image', $campaign->cover_image) }}"
                class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">
        </div>

        <!-- Timeline: Start & End Date -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                    Start Date (Optional)
                </label>
                <input type="date" name="start_date" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}"
                    class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                    End Date / Target Deadline (Optional)
                </label>
                <input type="date" name="end_date" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}"
                    class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">
            </div>
        </div>

        <!-- Summary -->
        <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                Short Campaign Summary
            </label>
            <textarea name="summary" rows="2"
                class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">{{ old('summary', $campaign->summary) }}</textarea>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-1.5">
                Full Narrative / Campaign Details
            </label>
            <textarea name="description" rows="5"
                class="w-full bg-slate-950 border border-slate-700 focus:border-purple-500 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none">{{ old('description', $campaign->description) }}</textarea>
        </div>

        <!-- Toggles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
            <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg bg-slate-950 border border-slate-800">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $campaign->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="rounded bg-slate-900 border-slate-700 text-purple-600 focus:ring-0 w-4 h-4">
                <div>
                    <span class="text-xs font-bold text-white block">Active &amp; Open for Donations</span>
                    <span class="text-[10px] text-slate-400">Allows donors to access the public link and contribute.</span>
                </div>
            </label>

            <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg bg-slate-950 border border-slate-800">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $campaign->is_featured ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="rounded bg-slate-900 border-slate-700 text-purple-600 focus:ring-0 w-4 h-4">
                <div>
                    <span class="text-xs font-bold text-white block">Featured Campaign</span>
                    <span class="text-[10px] text-slate-400">Showcases prominently at the top of the campaign portal.</span>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.campaigns.index') }}"
                class="px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-black text-xs uppercase tracking-wider shadow-lg transition cursor-pointer">
                Save Campaign Changes &rarr;
            </button>
        </div>
    </form>

</div>
@endsection
