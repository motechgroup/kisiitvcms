@extends('layouts.admin')

@section('title', 'SOD Subscribers & Finance Report — '.$liveStream->title)
@section('page_title', 'SOD Subscribers & Finance Report')

@section('content')
@php
    $currency = $liveStream->sod_currency ?: 'KES';
@endphp

<div class="space-y-6">
    <!-- Top Bar & Stream Summary -->
    <div class="bg-white border border-slate-200 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.streams.index') }}" class="text-xs font-bold text-[#1b52bc] hover:underline">
                    &larr; Back to Live Streams
                </a>
                <span class="text-slate-300">|</span>
                <span class="bg-amber-400 text-slate-950 text-[10px] font-black uppercase px-2 py-0.5 rounded">
                    SOD · {{ $currency }} {{ number_format((float) $liveStream->sod_price, 0) }}
                </span>
                <span class="bg-slate-800 text-slate-100 text-[10px] font-bold uppercase px-2 py-0.5 rounded">
                    {{ $liveStream->serve_on_main_live ? 'Served on /live' : 'Direct Link Only' }}
                </span>
            </div>
            <h2 class="text-lg font-black text-slate-900 font-headline">{{ $liveStream->title }}</h2>
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <code class="text-[11px] text-[#1b52bc] bg-blue-50 px-2 py-0.5 rounded font-mono">{{ $liveStream->sodUrl() }}</code>
                <button type="button"
                    onclick="navigator.clipboard.writeText('{{ $liveStream->sodUrl() }}'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy Link', 1800);"
                    class="px-2.5 py-0.5 rounded bg-slate-900 hover:bg-slate-700 text-white text-[10px] font-bold cursor-pointer">
                    Copy Link
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ $liveStream->sodUrl() }}" target="_blank"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-bold uppercase tracking-wider rounded transition">
                Open SOD Link &rarr;
            </a>
            <a href="{{ route('admin.streams.sod.export', $liveStream) }}"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded transition shadow-sm">
                Export Finance CSV
            </a>
        </div>
    </div>

    <!-- Finance KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Gross Paid Revenue</span>
            <span class="text-xl font-black text-emerald-600 mt-1 block font-headline">
                {{ $currency }} {{ number_format($grossPaidRevenue, 2) }}
            </span>
            <span class="text-[11px] text-slate-500 mt-0.5 block">{{ $paidPasses->count() }} paid pass{{ $paidPasses->count() === 1 ? '' : 'es' }}</span>
        </div>

        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Active Subscribers</span>
            <span class="text-xl font-black text-[#1b52bc] mt-1 block font-headline">{{ $activeSubscribersCount }}</span>
            <span class="text-[11px] text-slate-500 mt-0.5 block">Currently authorized to watch</span>
        </div>

        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Pending / Unpaid</span>
            <span class="text-xl font-black text-amber-600 mt-1 block font-headline">{{ $pendingPasses->count() }}</span>
            <span class="text-[11px] text-slate-500 mt-0.5 block">{{ $currency }} {{ number_format($pendingAmount, 0) }} initiated</span>
        </div>

        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Revoked Passes</span>
            <span class="text-xl font-black text-red-600 mt-1 block font-headline">{{ $revokedPasses->count() }}</span>
            <span class="text-[11px] text-slate-500 mt-0.5 block">{{ $currency }} {{ number_format($revokedAmount, 0) }} revoked</span>
        </div>

        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Checkout Conversion</span>
            <span class="text-xl font-black text-slate-900 mt-1 block font-headline">{{ $conversionRate }}%</span>
            <span class="text-[11px] text-slate-500 mt-0.5 block">{{ $paidPasses->count() }} of {{ $totalAttempts }} checkouts</span>
        </div>
    </div>

    <!-- Financial Breakdown: By Gateway & By Day -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gateway Breakdown -->
        <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                    Revenue Breakdown by Payment Gateway
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-4">Gateway</th>
                            <th class="py-2.5 px-4 text-center">Paid</th>
                            <th class="py-2.5 px-4 text-center">Pending</th>
                            <th class="py-2.5 px-4 text-center">Revoked</th>
                            <th class="py-2.5 px-4 text-right">Paid Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($gatewayBreakdown as $row)
                            <tr>
                                <td class="py-2.5 px-4 font-bold text-slate-800">{{ $row['gateway'] }}</td>
                                <td class="py-2.5 px-4 text-center text-emerald-700 font-bold">{{ $row['paid_count'] }}</td>
                                <td class="py-2.5 px-4 text-center text-amber-700">{{ $row['pending_count'] }}</td>
                                <td class="py-2.5 px-4 text-center text-red-600">{{ $row['revoked_count'] }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ $currency }} {{ number_format($row['paid_revenue'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No transactions recorded yet for this SOD stream.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daily Revenue Breakdown -->
        <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                    Daily Paid Revenue Summary
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-4">Date</th>
                            <th class="py-2.5 px-4 text-center">Paid Subscribers</th>
                            <th class="py-2.5 px-4 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($dailyRevenue as $day)
                            <tr>
                                <td class="py-2.5 px-4 font-mono font-semibold text-slate-800">{{ $day['date'] }}</td>
                                <td class="py-2.5 px-4 text-center font-bold text-slate-700">{{ $day['passes_count'] }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-700">
                                    {{ $currency }} {{ number_format($day['revenue'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">No paid revenue yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscribers Ledger & Status Management -->
    <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                    SOD Subscribers &amp; Access Control
                </h3>
                <p class="text-[11px] text-slate-500">
                    Revoking a subscriber immediately blocks playback on their device. Enabling restores access on their bound device.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.streams.sod', $liveStream) }}" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search name, phone, email, ref..."
                    class="px-3 py-1.5 text-xs border border-slate-300 rounded bg-white focus:border-[#1b52bc] outline-none w-56">
                <select name="status" class="px-3 py-1.5 text-xs border border-slate-300 rounded bg-white focus:border-[#1b52bc] outline-none">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid / Active</option>
                    <option value="revoked" {{ $statusFilter === 'revoked' ? 'selected' : '' }}>Revoked</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="expired" {{ $statusFilter === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
                <button type="submit"
                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded cursor-pointer">
                    Filter
                </button>
                @if($search !== '' || $statusFilter !== 'all')
                    <a href="{{ route('admin.streams.sod', $liveStream) }}" class="text-xs text-slate-500 hover:underline px-1">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Order Ref</th>
                        <th class="py-3 px-4">Subscriber</th>
                        <th class="py-3 px-4">Amount &amp; Gateway</th>
                        <th class="py-3 px-4 text-center">Access Status</th>
                        <th class="py-3 px-4">Device &amp; Activity</th>
                        <th class="py-3 px-4 text-right">Manage Access</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($passes as $pass)
                        @php
                            $isExpired = $pass->status === 'paid' && $pass->expires_at && $pass->expires_at->isPast();
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900">{{ $pass->order_reference }}</div>
                                @if($pass->external_reference)
                                    <div class="text-[10px] font-mono text-slate-400">Ext: {{ $pass->external_reference }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $pass->buyer_name ?: 'SOD Viewer' }}</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $pass->buyer_phone ?: 'No phone' }}
                                    @if($pass->buyer_email)
                                        &middot; {{ $pass->buyer_email }}
                                    @endif
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900">
                                    {{ $pass->currency }} {{ number_format((float) $pass->amount, 2) }}
                                </div>
                                <div class="text-[10px] uppercase font-bold text-slate-400">{{ strtoupper((string) $pass->gateway_code) }}</div>
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($pass->status === 'revoked')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-red-100 text-red-800 text-[10px] font-black uppercase">
                                        Revoked
                                    </span>
                                @elseif($isExpired)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px] font-black uppercase">
                                        Expired
                                    </span>
                                @elseif($pass->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">
                                        Active / Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-black uppercase">
                                        Pending
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-[11px] text-slate-600 space-y-0.5">
                                <div>
                                    @if($pass->device_fingerprint_hash)
                                        <span class="text-emerald-700 font-semibold">Device Locked</span>
                                    @else
                                        <span class="text-slate-400">Not Bound</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    Paid: {{ $pass->paid_at ? $pass->paid_at->format('M d, H:i') : '—' }}
                                    @if($pass->last_watched_at)
                                        &middot; Watched: {{ $pass->last_watched_at->diffForHumans() }}
                                    @endif
                                </div>
                            </td>

                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($pass->isPaidAndActive())
                                    <form method="POST" action="{{ route('admin.streams.sod.passes.status', [$liveStream, $pass]) }}" class="inline"
                                        onsubmit="return confirm('Revoke streaming access for {{ $pass->order_reference }}? They will be blocked immediately.')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="revoked">
                                        <button type="submit"
                                            class="px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-wider transition cursor-pointer">
                                            Revoke Access
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.streams.sod.passes.status', [$liveStream, $pass]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="paid">
                                        <button type="submit"
                                            class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-wider transition cursor-pointer">
                                            Enable Access
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No SOD subscribers found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($passes->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $passes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
