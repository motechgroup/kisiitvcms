@extends('layouts.admin')

@section('title', 'Profile Settings & Account Information - Kisii TV')
@section('page_title', 'Staff Profile & Account Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    avatarPreview: null,
    previewAvatar(e) {
        const file = e.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    }
}">

    <!-- Header Breadcrumbs / Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">Profile Settings &amp; Information</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage your editorial credentials, public username, byline information, and security credentials.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('home') }}" target="_blank" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>Live Portal</span>
            </a>
            <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                &larr; Newsroom Desk
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-900 text-xs rounded shadow-sm">
            <div class="font-bold mb-1">Please correct the following:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Profile Overview Card with Avatar -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <!-- Avatar Preview -->
                <div class="relative shrink-0">
                    <img :src="avatarPreview || '{{ $user->avatar_url }}'" alt="{{ $user->name }}"
                        class="w-24 h-24 rounded-full object-cover border-4 border-slate-100 shadow-sm bg-slate-100">
                    <label class="absolute bottom-0 right-0 p-1.5 bg-[#1b52bc] hover:bg-blue-800 text-white rounded-full cursor-pointer shadow transition" title="Upload New Photo">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" name="avatar" accept="image/*" @change="previewAvatar" class="hidden">
                    </label>
                </div>

                <!-- User Meta Information -->
                <div class="flex-grow space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-black text-slate-900 font-headline">{{ $user->name }}</h3>
                        <span class="px-2 py-0.5 bg-blue-100 text-[#1b52bc] font-mono text-[11px] font-bold rounded">
                            {{ '@' . $user->username }}
                        </span>
                        @foreach($user->roles as $role)
                            <span class="px-2 py-0.5 bg-red-100 text-[#d9252a] text-[10px] font-extrabold uppercase rounded tracking-wider">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 font-medium">{{ $user->job_title ?? 'Kisii TV Newsroom Staff' }} &bull; {{ $user->email }}</p>
                    <p class="text-[11px] text-slate-400">Staff account active since {{ $user->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Identity & Newsroom Information -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2">
                1. Personal &amp; Byline Information
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Full Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Username (Display Handle) <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-mono font-bold">@</span>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                            class="w-full text-xs p-2.5 pl-7 border border-slate-300 rounded font-mono focus:border-[#1b52bc]"
                            placeholder="username">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Displayed on the header and reporter bylines.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Email Address <span class="text-red-600">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Phone Number (WhatsApp / Hotline)
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+254 7..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Job Title &amp; Bureau Desk
                    </label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Senior Political Editor / Bureau Chief"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Editorial Bio / Credentials
                    </label>
                    <textarea name="bio" rows="3" placeholder="Brief background, beats covered, or profile overview..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Security & Password Change -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2">
                2. Security &amp; Password Update
            </h3>
            <p class="text-[11px] text-slate-500">Leave these password fields blank if you do not wish to change your current password.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        New Password
                    </label>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Minimum 8 characters"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Confirm New Password
                    </label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat new password"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded shadow transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Save Profile Changes</span>
            </button>
        </div>

    </form>
</div>
@endsection
