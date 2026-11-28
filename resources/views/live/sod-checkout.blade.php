@extends('layouts.news')

@section('content')
<div class="bg-slate-950 text-white py-12 border-b-4 border-[#d9252a]">
    <div class="max-w-xl mx-auto px-4 sm:px-6">
        @if(session('error'))
            <div class="mb-6 bg-red-950/90 border-l-4 border-red-500 text-red-100 p-4 rounded text-xs sm:text-sm">
                <strong class="uppercase mr-1">Notice:</strong> {{ session('error') }}
            </div>
        @endif

        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-[#0a1c3d] px-6 py-5 border-b border-slate-800">
                <span class="inline-block bg-[#d9252a]/20 text-red-300 border border-[#d9252a]/40 text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded mb-2">
                    SOD Stream Pass Checkout
                </span>
                <h1 class="text-xl font-black font-headline text-white">{{ $pass->liveStream->title }}</h1>
                <p class="text-xs text-slate-300 mt-1">Order Reference: <span class="font-mono text-white">{{ $pass->order_reference }}</span></p>
            </div>

            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Amount Due</span>
                        <span class="text-2xl font-black text-emerald-400">{{ $pass->currency }} {{ number_format((float) $pass->amount, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Gateway</span>
                        <span class="text-sm font-bold text-white">{{ $pass->paymentGateway?->name ?? strtoupper($pass->gateway_code) }}</span>
                    </div>
                </div>

                @if($pass->paymentGateway?->instructions)
                    <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-500/30 text-xs text-amber-200 leading-relaxed">
                        <strong class="block uppercase text-[10px] tracking-wider text-amber-400 mb-1">Payment Instructions</strong>
                        {{ $pass->paymentGateway->instructions }}
                    </div>
                @endif

                <form method="POST" action="{{ route('sod.checkout.complete', $pass->order_reference) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Transaction / Confirmation Code <span class="text-slate-500 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="external_reference" value="{{ old('external_reference') }}"
                            placeholder="e.g. M-Pesa or Gateway Receipt Code"
                            class="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-[#d9252a] outline-none">
                    </div>

                    <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase tracking-wider text-xs sm:text-sm transition cursor-pointer">
                        Confirm Payment &amp; Unlock Stream on This Device
                    </button>
                </form>

                <div class="text-center">
                    <a href="{{ route('sod.show', $pass->liveStream->slug) }}" class="text-xs text-slate-400 hover:text-white underline">
                        ← Back to Stream Page
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
