@extends('layouts.admin')

@section('title', 'Donation Campaigns Management — Kisii TV Newsroom')

@section('content')
<div class="space-y-6" x-data="{ copiedId: null }">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-700">
        <div>
            <h1 class="text-xl sm:text-2xl font-black font-headline text-white tracking-tight">
                Donation Campaigns &amp; Special Funds
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Create targeted fundraising campaigns, generate direct shareable links, and track contributions in real-time.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('campaigns.index') }}" target="_blank"
                class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wider transition flex items-center space-x-1.5 border border-slate-700">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>View Public Portal</span>
            </a>
            <a href="{{ route('admin.campaigns.create') }}"
                class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-black uppercase tracking-wider shadow-lg transition flex items-center space-x-1.5 cursor-pointer">
                <span>+ Create Campaign</span>
            </a>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-[#0a1c3d] text-slate-400 uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Campaign</th>
                        <th class="px-4 py-3.5">Target &amp; Progress</th>
                        <th class="px-4 py-3.5">Backers</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Shareable Direct Link</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($campaigns as $camp)
                        <tr class="hover:bg-slate-800/50 transition">
                            <!-- Title & Image -->
                            <td class="px-5 py-4">
                                <div class="flex items-center space-x-3">
                                    @if($camp->cover_image)
                                        <img src="{{ $camp->cover_image }}" alt="{{ $camp->title }}"
                                            class="w-12 h-12 rounded-lg object-cover border border-slate-700 shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center shrink-0 text-slate-500 font-bold text-xs">
                                            KTV
                                        </div>
                                    @endif
                                    <div class="min-w-0 max-w-xs sm:max-w-sm">
                                        <div class="font-bold text-white text-sm truncate">{{ $camp->title }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono truncate">/campaigns/{{ $camp->slug }}</div>
                                        @if($camp->is_featured)
                                            <span class="inline-block mt-1 bg-purple-900 text-purple-200 text-[9px] font-bold px-1.5 py-0.2 rounded">
                                                Featured
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Target & Raised -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="space-y-1 w-36">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-emerald-400 font-mono">{{ $camp->currency }} {{ number_format($camp->total_raised ?? 0) }}</span>
                                        <span class="text-slate-400 font-mono">{{ $camp->progressPercentage() }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden border border-slate-800">
                                        <div class="bg-gradient-to-r from-emerald-500 to-purple-500 h-1.5 rounded-full"
                                            style="width: {{ $camp->progressPercentage() }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">
                                        Goal: {{ $camp->currency }} {{ number_format((float) $camp->target_amount) }}
                                    </div>
                                </div>
                            </td>

                            <!-- Backers -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-bold text-white font-mono text-sm">{{ $camp->total_donors ?? 0 }}</span>
                                <span class="text-[10px] text-slate-400 block">donations</span>
                            </td>

                            <!-- Status Toggle -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.campaigns.toggle-active', $camp) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider transition cursor-pointer {{ $camp->is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 hover:bg-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                                        {{ $camp->is_active ? '● Active' : '○ Closed' }}
                                    </button>
                                </form>
                            </td>

                            <!-- Shareable Direct Link -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <input type="text" readonly value="{{ $camp->shareUrl() }}"
                                        id="share_input_{{ $camp->id }}"
                                        class="bg-slate-950 border border-slate-700 text-slate-300 rounded px-2.5 py-1 text-[11px] font-mono w-44 truncate select-all focus:outline-none">
                                    <button type="button"
                                        @click="
                                            navigator.clipboard.writeText('{{ $camp->shareUrl() }}');
                                            copiedId = {{ $camp->id }};
                                            setTimeout(() => { copiedId = null; }, 2000);
                                        "
                                        title="Copy link to clipboard"
                                        class="px-2.5 py-1 rounded bg-purple-600 hover:bg-purple-500 text-white font-bold text-[10px] uppercase tracking-wider transition cursor-pointer flex items-center space-x-1 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <span x-text="copiedId === {{ $camp->id }} ? 'Copied!' : 'Copy Link'"></span>
                                    </button>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.campaigns.show', $camp) }}"
                                    class="text-blue-400 hover:text-white transition font-semibold" title="View Donors & Stats">
                                    Donors
                                </a>
                                <a href="{{ route('admin.campaigns.edit', $camp) }}"
                                    class="text-amber-400 hover:text-white transition font-semibold" title="Edit Campaign">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.campaigns.destroy', $camp) }}" class="inline-block"
                                    onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 font-semibold cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                No donation campaigns created yet. Click "+ Create Campaign" above to launch your first fundraising campaign!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
