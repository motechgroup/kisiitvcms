@extends('layouts.admin')

@section('title', 'Newsroom Staff & Users')
@section('page_title', 'Newsroom Staff & User Management')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 font-headline">Editorial Staff &amp; System Access</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage journalists, section editors, administrators, and assign role-based newsroom privileges.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded shadow transition flex items-center space-x-1.5 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>+ Add Newsroom User</span>
        </a>
    </div>

    <!-- Quick Stats Tiles -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Total Staff Accounts</span>
            <span class="text-2xl font-black text-slate-900 font-headline mt-1 block">{{ $stats['total'] }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 block">Active &amp; Authorized</span>
            <span class="text-2xl font-black text-emerald-700 font-headline mt-1 block">{{ $stats['active'] }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded shadow-sm">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Deactivated Accounts</span>
            <span class="text-2xl font-black text-slate-500 font-headline mt-1 block">{{ $stats['inactive'] }}</span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or job title..."
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
            </div>

            <div>
                <select name="role" class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded text-center">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Contact Email &amp; Phone</th>
                        <th class="py-3 px-4">Assigned Roles</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Registered</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-[#0a1c3d] text-white font-bold flex items-center justify-center shrink-0 uppercase text-xs">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span class="text-[9px] bg-blue-50 text-[#1b52bc] px-1.5 py-0.2 rounded font-bold">You</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-500 block">{{ $user->job_title ?? 'Staff' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="font-mono text-slate-700 block">{{ $user->email }}</span>
                                @if($user->phone)
                                    <span class="text-[10px] text-slate-400 block">{{ $user->phone }}</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded {{ $role->slug === 'super-admin' ? 'bg-red-100 text-[#d9252a]' : 'bg-blue-100 text-[#1b52bc]' }}">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-[10px]">No role</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($user->is_active)
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-emerald-100 text-emerald-800">Active</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-slate-200 text-slate-600">Inactive</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center text-slate-500 text-[10px]">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>

                            <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-[#1b52bc] hover:underline font-bold">
                                    Edit
                                </a>

                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-slate-600 hover:text-slate-900 font-bold">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Remove user {{ $user->name }} from newsroom access?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-bold">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No staff members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
