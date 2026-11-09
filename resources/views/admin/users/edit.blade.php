@extends('layouts.admin')

@section('title', 'Edit Staff: ' . $user->name)
@section('page_title', 'Edit Newsroom Staff Account')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">Edit Staff Member</h2>
            <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            &larr; Back to Users
        </a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 lg:p-8 space-y-6">
        @csrf
        @method('PUT')

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
                    Email Address <span class="text-red-600">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Password (Leave blank to keep unchanged)
                </label>
                <input type="password" name="password" placeholder="New password..."
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Job Title / Desk
                </label>
                <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Direct Phone Number
                </label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div class="flex items-center pt-5">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        {{ $user->id === auth()->id() ? 'disabled' : '' }} class="rounded border-slate-300 text-[#1b52bc]">
                    <span class="text-xs font-bold text-slate-800">Account is Active &amp; Authorized</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                Journalist Biography
            </label>
            <textarea name="bio" rows="3"
                class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">{{ old('bio', $user->bio) }}</textarea>
        </div>

        <!-- Role Assignment -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-3">
            <div class="font-bold text-xs uppercase tracking-wider text-slate-900">
                Assigned Newsroom Roles <span class="text-red-600">*</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                @php
                    $userRoleIds = old('role_ids', $user->roles->pluck('id')->all());
                @endphp
                @foreach($roles as $role)
                    <label class="flex items-start p-2.5 bg-white border border-slate-200 rounded cursor-pointer hover:bg-slate-50">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                        <div class="ml-2.5">
                            <span class="block text-xs font-bold text-slate-900">{{ $role->name }}</span>
                            <span class="text-[10px] text-slate-500 line-clamp-1">{{ $role->description }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-400">Created: {{ $user->created_at->format('M d, Y') }}</span>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-slate-300 text-xs font-bold uppercase text-slate-600 rounded hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded shadow transition">
                    Update User &rarr;
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
