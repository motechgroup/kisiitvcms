@extends('layouts.admin')

@section('title', 'Campaign Ledger: ' . $campaign->title . ' — Kisii TV Newsroom')

@section('content')
<div class="space-y-6" x-data="{ copied: false }">

    <!-- Top Navigation & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-700">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-xs uppercase font-bold text-purple-400">Campaign Details &amp; Ledger</span>
                <span class="text-slate-600">•</span>
                <span class="text-xs font-mono text-slate-400">/campaigns/{{ $campaign->slug }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black font-headline text-white tracking-tight mt-0.5">
                {{ $campaign->title }}
            </h1>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('campaigns.show', $campaign->slug) }}" target="_blank"
                class="px-3.5 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-wider transition shadow flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>View Public Page</span>
            </a>
            <a href="{{ route('admin.campaigns.edit', $campaign) }}"
                class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wider transition border border-slate-700">
                Edit Campaign
            </a>
            <a href="{{ route('admin.campaigns.index') }}"
                class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wider transition">
                &larr; Back
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards & Share Link Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Raised</span>
            <span class="text-2xl font-black text-emerald-400 font-mono mt-1 block">
                {{ $campaign->currency }} {{ number_format($campaign->totalRaised()) }}
            </span>
            <span class="text-[11px] text-slate-500 mt-1 block">
                Goal: {{ $campaign->currency }} {{ number_format((float) $campaign->target_amount) }}
            </span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Goal Progress</span>
            <span class="text-2xl font-black text-purple-400 font-mono mt-1 block">
                {{ $campaign->progressPercentage() }}%
            </span>
            <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden border border-slate-800 mt-2">
                <div class="bg-gradient-to-r from-emerald-500 to-purple-500 h-1.5 rounded-full"
                    style="width: {{ $campaign->progressPercentage() }}%"></div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Verified Donors</span>
            <span class="text-2xl font-black text-white font-mono mt-1 block">
                {{ $campaign->donorCount() }}
            </span>
            <span class="text-[11px] text-slate-500 mt-1 block">Supporters back this cause</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Campaign Status</span>
            <span class="text-lg font-bold mt-1 block {{ $campaign->is_active ? 'text-emerald-400' : 'text-slate-400' }}">
                {{ $campaign->is_active ? 'Active & Open' : 'Closed' }}
            </span>
            <span class="text-[11px] text-slate-500 mt-1 block">
                {{ $campaign->end_date ? 'Ends ' . $campaign->end_date->format('M j, Y') : 'Ongoing initiative' }}
            </span>
        </div>
    </div>

    <!-- Shareable Link Card -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950/40 to-slate-900 border border-purple-500/40 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
        <div class="space-y-1">
            <span class="text-[10px] uppercase font-black tracking-widest text-purple-400 block">Shareable Campaign Donation URL</span>
            <p class="text-xs text-slate-300">Share this direct link across WhatsApp, broadcast lower-thirds, social media, and newsletters:</p>
        </div>
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <input type="text" readonly value="{{ $campaign->shareUrl() }}"
                class="bg-slate-950 border border-slate-700 text-purple-300 rounded-lg px-3 py-2 text-xs font-mono select-all focus:outline-none w-full sm:w-80">
            <button type="button"
                @click="
                    navigator.clipboard.writeText('{{ $campaign->shareUrl() }}');
                    copied = true;
                    setTimeout(() => { copied = false; }, 2000);
                "
                class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-black text-xs uppercase tracking-wider transition cursor-pointer shrink-0">
                <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
            </button>
        </div>
    </div>

    <!-- Campaign Donations Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-xl overflow-hidden">
        <div class="p-4 bg-[#0a1c3d] border-b border-slate-800 flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-white">
                Donations Received Under This Campaign
            </span>
            <span class="text-xs text-slate-400 font-mono">
                {{ $donations->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-4 py-3">Supporter</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Gateway</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($donations as $donation)
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-5 py-3.5 font-mono text-[11px] text-white">
                                {{ $donation->transaction_reference }}
                                @if($donation->external_reference)
                                    <span class="text-[10px] text-slate-500 block font-mono">{{ $donation->external_reference }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-white">{{ $donation->displayName() }}</span>
                                @if($donation->donor_phone)
                                    <span class="text-[10px] text-slate-400 block font-mono">{{ $donation->donor_phone }}</span>
                                @endif
                                @if($donation->message)
                                    <p class="text-[11px] text-slate-400 italic mt-0.5 line-clamp-1">"{{ $donation->message }}"</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-emerald-400 text-sm">
                                {{ $donation->currency }} {{ number_format((float) $donation->amount, 2) }}
                            </td>
                            <td class="px-4 py-3.5 uppercase font-mono text-[10px] text-blue-300">
                                {{ $donation->gateway_code }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($donation->status === 'completed')
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold uppercase">
                                        Completed
                                    </span>
                                @elseif($donation->status === 'pending')
                                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-bold uppercase">
                                        Pending
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-300 border border-red-500/40 text-[10px] font-bold uppercase">
                                        {{ $donation->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right text-slate-400 text-[11px]">
                                {{ $donation->created_at->format('M j, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                No contributions received for this campaign yet. Share the link above to start collecting donations!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($donations->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $donations->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
