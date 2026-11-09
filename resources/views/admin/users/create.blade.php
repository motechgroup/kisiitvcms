@extends('layouts.admin')

@section('title', 'Add Newsroom Staff Member')
@section('page_title', 'Create Newsroom Staff Account')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">New Staff Account</h2>
            <p class="text-xs text-slate-500 mt-0.5">Authorize a new journalist, editor, or administrator with system permissions.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            &larr; Back to Users
        </a>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 lg:p-8 space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Kelvin Mokaya"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Email Address <span class="text-red-600">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="staff@kisiitv.com"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Password <span class="text-red-600">*</span>
                </label>
                <input type="password" name="password" required placeholder="Minimum 8 characters..."
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Job Title / Desk
                </label>
                <input type="text" name="job_title" value="{{ old('job_title') }}" placeholder="e.g. Senior Crime Reporter"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Direct Phone Number
                </label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+254 700 000 000"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div class="flex items-center pt-5">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-[#1b52bc]">
                    <span class="text-xs font-bold text-slate-800">Account is Active &amp; Authorized</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                Journalist Biography
            </label>
            <textarea name="bio" rows="3" placeholder="Brief editorial profile and journalistic credentials..."
                class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">{{ old('bio') }}</textarea>
        </div>

        <!-- Role Assignment -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-3">
            <div class="font-bold text-xs uppercase tracking-wider text-slate-900">
                Assign Newsroom Roles <span class="text-red-600">*</span>
            </div>
            <p class="text-[11px] text-slate-500">Select one or more roles defining system privileges:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                @foreach($roles as $role)
                    <label class="flex items-start p-2.5 bg-white border border-slate-200 rounded cursor-pointer hover:bg-slate-50">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" {{ in_array($role->id, old('role_ids', [])) ? 'checked' : '' }}
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
        <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-slate-300 text-xs font-bold uppercase text-slate-600 rounded hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded shadow transition">
                Create Staff Account &rarr;
            </button>
        </div>
    </form>

</div>
@endsection
