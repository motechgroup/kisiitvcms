@extends('layouts.news')

@section('content')
<div class="bg-slate-950 text-white py-12 border-b-4 border-[#d9252a]">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-[#0a1c3d] px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 block">Secure Payment Gateway Checkout</span>
                    <h1 class="text-lg sm:text-xl font-black font-headline text-white mt-0.5">
                        Complete Your {{ ucfirst($donation->gateway_code) }} Contribution
                    </h1>
                </div>
                <span class="px-3 py-1 rounded bg-slate-950 border border-slate-700 font-mono text-xs text-slate-300">
                    {{ $donation->transaction_reference }}
                </span>
            </div>

            <!-- Summary Details -->
            <div class="p-6 space-y-6">
                <div class="bg-slate-950 border border-slate-800 rounded-lg p-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Amount Due</span>
                        <span class="text-lg font-black text-emerald-400">{{ $donation->currency }} {{ number_format((float) $donation->amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Supporter</span>
                        <span class="font-bold text-white block mt-0.5">{{ $donation->donor_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Payment Gateway</span>
                        <span class="font-bold text-blue-300 uppercase block mt-0.5">{{ $donation->paymentGateway?->name ?? strtoupper($donation->gateway_code) }}</span>
                    </div>
                </div>

                @if($donation->liveStream)
                    <div class="text-xs text-slate-300 bg-slate-950/60 border border-slate-800 p-3.5 rounded flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-red-400 block">Supporting Live Stream:</span>
                            <span class="font-bold text-white">{{ $donation->liveStream->title }}</span>
                        </div>
                        <a href="{{ route('live.show', $donation->liveStream->slug) }}" class="text-blue-400 hover:underline font-semibold shrink-0 ml-3">
                            &larr; Back to Stream
                        </a>
                    </div>
                @endif

                <!-- Gateway Specific Instructions & Interactive Checkout -->
                @if($donation->gateway_code === 'mpesa')
                    <div class="bg-emerald-950/30 border border-emerald-500/40 rounded-lg p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Safaricom M-Pesa Express / Paybill</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Sandbox / Test Mode' : 'Live Daraja Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $donation->paymentGateway?->instructions ?: 'Check your phone for an M-Pesa STK Push prompt or send via Paybill below and enter your M-Pesa confirmation code.' }}
                        </p>
                        <div class="grid grid-cols-2 gap-3 bg-slate-950 p-3 rounded border border-slate-800 text-xs">
                            <div>
                                <span class="text-[10px] uppercase text-slate-400 block">Paybill / Till Shortcode</span>
                                <span class="font-mono font-black text-white text-sm">{{ $donation->paymentGateway?->shortcode_or_merchant_id ?: '174379' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase text-slate-400 block">Account Number</span>
                                <span class="font-mono font-black text-white text-sm">{{ $donation->paymentGateway?->account_reference ?: 'KISIITV' }}</span>
                            </div>
                        </div>
                    </div>
                @elseif($donation->gateway_code === 'paystack')
                    <div class="bg-blue-950/30 border border-blue-500/40 rounded-lg p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-blue-400">Paystack Secure Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-500/20 text-blue-300">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Test Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $donation->paymentGateway?->instructions ?: 'Complete your contribution via Paystack using Card, Mobile Money, or Bank Transfer.' }}
                        </p>
                    </div>
                @elseif($donation->gateway_code === 'paypal')
                    <div class="bg-indigo-950/30 border border-indigo-500/40 rounded-lg p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-indigo-300">PayPal International Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-200">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Sandbox Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $donation->paymentGateway?->instructions ?: 'Pay securely with your PayPal wallet or international debit/credit card.' }}
                        </p>
                    </div>
                @elseif($donation->gateway_code === 'flutterwave')
                    <div class="bg-amber-950/30 border border-amber-500/40 rounded-lg p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-amber-400">Flutterwave Multi-Channel Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/20 text-amber-200">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Test Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $donation->paymentGateway?->instructions ?: 'Complete payment via Flutterwave using Card, M-Pesa, or Mobile Money.' }}
                        </p>
                    </div>
                @endif

                <!-- Payment Confirmation Form -->
                <form method="POST" action="{{ route('donations.complete', $donation->transaction_reference) }}" class="space-y-4 pt-2 border-t border-slate-800">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Transaction / Receipt Reference (Optional in Test Mode)
                        </label>
                        <input type="text" name="external_reference"
                            placeholder="{{ $donation->gateway_code === 'mpesa' ? 'e.g. QKA82M9X1P (M-Pesa Receipt Code)' : 'Gateway reference or leave blank to auto-verify' }}"
                            class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded px-3.5 py-2.5 text-xs text-white focus:outline-none">
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="{{ $donation->liveStream ? route('live.show', $donation->liveStream->slug) : route('live.index') }}"
                            class="w-full sm:w-auto text-center px-4 py-2.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                            Cancel &amp; Return
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider shadow-lg transition cursor-pointer">
                            Confirm &amp; Complete {{ ucfirst($donation->gateway_code) }} Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
