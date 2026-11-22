@extends('layouts.admin')

@section('title', 'Viewer Donations & Contributions Ledger')
@section('page_title', 'Live Stream Donations & Payment Transactions')

@section('content')
<div class="space-y-8">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Donations &amp; Live Stream Support Ledger</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Track and verify viewer contributions processed via M-Pesa, Paystack, PayPal, and Flutterwave.
            </p>
        </div>
        <div class="flex items-center space-x-2.5 shrink-0">
            <a href="{{ route('admin.streams.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold uppercase tracking-wider rounded transition">
                Live Stream Studio
            </a>
            <a href="{{ route('admin.payment-gateways.index') }}" class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                Manage Payment Gateways
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Completed Donations</span>
            <span class="text-xl font-black text-emerald-600 mt-1 block font-headline">KES {{ number_format($totalRaised, 2) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Verified Contributions</span>
            <span class="text-xl font-black text-slate-900 mt-1 block font-headline">{{ number_format($completedCount) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Pending Checkout Confirmation</span>
            <span class="text-xl font-black text-amber-600 mt-1 block font-headline">{{ number_format($pendingCount) }}</span>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.donations.index') }}" class="bg-white p-4 border border-slate-200 shadow-sm flex flex-wrap items-end gap-3 text-xs">
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Payment Method</label>
            <select name="gateway" class="border border-slate-300 rounded px-3 py-1.5 bg-white">
                <option value="">All Gateways</option>
                @foreach($gateways as $gw)
                    <option value="{{ $gw->code }}" {{ request('gateway') === $gw->code ? 'selected' : '' }}>{{ $gw->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status</label>
            <select name="status" class="border border-slate-300 rounded px-3 py-1.5 bg-white">
                <option value="">All Statuses</option>
                @foreach(['completed', 'pending', 'failed', 'cancelled'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Live Stream</label>
            <select name="live_stream_id" class="border border-slate-300 rounded px-3 py-1.5 bg-white">
                <option value="">All Live Streams</option>
                @foreach($streams as $st)
                    <option value="{{ $st->id }}" {{ (string) request('live_stream_id') === (string) $st->id ? 'selected' : '' }}>{{ $st->title }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-1.5 bg-[#0a1c3d] text-white font-bold uppercase tracking-wider rounded cursor-pointer">
            Filter
        </button>
        @if(request()->hasAny(['gateway', 'status', 'live_stream_id']))
            <a href="{{ route('admin.donations.index') }}" class="px-3 py-1.5 text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </form>

    <!-- Donations Table -->
    <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Reference</th>
                        <th class="py-3 px-4">Donor</th>
                        <th class="py-3 px-4">Live Stream</th>
                        <th class="py-3 px-4">Gateway</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($donations as $donation)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono">
                                <div class="font-bold text-slate-900">{{ $donation->transaction_reference }}</div>
                                @if($donation->external_reference)
                                    <div class="text-[10px] text-slate-400">Ext: {{ $donation->external_reference }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">
                                    {{ $donation->donor_name }}
                                    @if($donation->is_anonymous)
                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase">Anon</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $donation->donor_phone ?: $donation->donor_email }}
                                </div>
                                @if($donation->message)
                                    <div class="text-[11px] italic text-slate-500 mt-0.5">"{{ $donation->message }}"</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($donation->liveStream)
                                    <a href="{{ route('live.show', $donation->liveStream->slug) }}" target="_blank" class="font-semibold text-[#1b52bc] hover:underline line-clamp-1">
                                        {{ $donation->liveStream->title }}
                                    </a>
                                @else
                                    <span class="text-slate-400">General Broadcast Fund</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 uppercase font-bold text-[11px]">
                                {{ $donation->gateway_code }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                {{ $donation->currency }} {{ number_format((float) $donation->amount, 2) }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                    {{ $donation->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $donation->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ in_array($donation->status, ['failed', 'cancelled']) ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $donation->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.donations.update-status', $donation) }}" class="inline-flex items-center space-x-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded px-2 py-1 text-[11px] bg-white">
                                        @foreach(['pending', 'completed', 'failed', 'cancelled'] as $statusOption)
                                            <option value="{{ $statusOption }}" {{ $donation->status === $statusOption ? 'selected' : '' }}>
                                                {{ ucfirst($statusOption) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>

                                <form method="POST" action="{{ route('admin.donations.destroy', $donation) }}" class="inline ml-2" onsubmit="return confirm('Delete this donation entry?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline font-semibold cursor-pointer">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No donation transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($donations->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $donations->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
