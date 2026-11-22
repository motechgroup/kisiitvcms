@extends('layouts.news')

@section('title', 'Checkout — Complete Your Donation to Kisii TV')

@section('content')
<div class="bg-slate-950 text-white py-12 border-b-4 border-[#d9252a]"
    x-data="{
        stkLoading: false,
        stkSent: false,
        stkMessage: '',
        stkSuccess: false,
        pollingTimer: null,
        phone: '{{ $donation->donor_phone }}',
        externalRef: '{{ old('external_reference', $donation->external_reference) }}',

        triggerStkPush() {
            this.stkLoading = true;
            this.stkMessage = 'Contacting Safaricom M-Pesa Daraja gateway...';
            
            fetch('{{ route('donations.stk-push', $donation->transaction_reference) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone: this.phone })
            })
            .then(res => res.json())
            .then(data => {
                this.stkLoading = false;
                this.stkSent = true;
                this.stkSuccess = data.success;
                this.stkMessage = data.message;

                if (data.success) {
                    this.startPolling();
                }
            })
            .catch(err => {
                this.stkLoading = false;
                this.stkSent = true;
                this.stkSuccess = false;
                this.stkMessage = 'Could not initiate STK push prompt. Please use manual Paybill 174379.';
            });
        },

        startPolling() {
            if (this.pollingTimer) clearInterval(this.pollingTimer);
            let attempts = 0;
            this.pollingTimer = setInterval(() => {
                attempts++;
                if (attempts > 20) {
                    clearInterval(this.pollingTimer);
                    return;
                }

                fetch('{{ route('donations.status', $donation->transaction_reference) }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.is_completed) {
                        clearInterval(this.pollingTimer);
                        window.location.href = data.redirect_url;
                    }
                })
                .catch(() => {});
            }, 3000);
        },

        payWithPaystack() {
            @if($donation->paymentGateway && !empty($donation->paymentGateway->public_key) && !$donation->paymentGateway->is_test_mode)
                let handler = PaystackPop.setup({
                    key: '{{ $donation->paymentGateway->public_key }}',
                    email: '{{ $donation->donor_email ?: "donations@kisiitv.co.ke" }}',
                    amount: Math.round({{ $donation->amount }} * 100),
                    currency: '{{ $donation->currency ?: "KES" }}',
                    ref: '{{ $donation->transaction_reference }}',
                    metadata: {
                        donor_name: '{{ addslashes($donation->donor_name) }}',
                        donor_phone: '{{ $donation->donor_phone }}'
                    },
                    callback: (response) => {
                        this.externalRef = response.reference;
                        $nextTick(() => document.getElementById('payment_complete_form').submit());
                    },
                    onClose: () => {
                        alert('Paystack checkout window was closed.');
                    }
                });
                handler.openIframe();
            @else
                this.externalRef = 'PSTK-' + Math.random().toString(36).substring(2, 10).toUpperCase();
                document.getElementById('payment_complete_form').submit();
            @endif
        },

        payWithFlutterwave() {
            @if($donation->paymentGateway && !empty($donation->paymentGateway->public_key) && !$donation->paymentGateway->is_test_mode)
                FlutterwaveCheckout({
                    public_key: '{{ $donation->paymentGateway->public_key }}',
                    tx_ref: '{{ $donation->transaction_reference }}',
                    amount: {{ $donation->amount }},
                    currency: '{{ $donation->currency ?: "KES" }}',
                    payment_options: 'card,mobilemoneykenya,ussd',
                    customer: {
                        email: '{{ $donation->donor_email ?: "donations@kisiitv.co.ke" }}',
                        phone_number: '{{ $donation->donor_phone ?: "" }}',
                        name: '{{ addslashes($donation->donor_name) }}'
                    },
                    customizations: {
                        title: 'Kisii TV Support',
                        description: 'Donation {{ $donation->transaction_reference }}',
                        logo: '{{ asset("images/kisii-tv-logo.png") }}'
                    },
                    callback: (data) => {
                        this.externalRef = data.transaction_id || data.tx_ref;
                        $nextTick(() => document.getElementById('payment_complete_form').submit());
                    }
                });
            @else
                this.externalRef = 'FLW-' + Math.random().toString(36).substring(2, 10).toUpperCase();
                document.getElementById('payment_complete_form').submit();
            @endif
        }
    }">

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

                <!-- Contextual Attribution: Campaign, Live Stream, or General -->
                @if($donation->campaign)
                    <div class="text-xs text-slate-300 bg-purple-950/40 border border-purple-500/40 p-3.5 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-purple-400 block">Supporting Campaign:</span>
                            <span class="font-bold text-white">{{ $donation->campaign->title }}</span>
                        </div>
                        <a href="{{ route('campaigns.show', $donation->campaign->slug) }}" class="text-purple-300 hover:text-white underline font-semibold shrink-0 ml-3">
                            &larr; View Campaign
                        </a>
                    </div>
                @elseif($donation->liveStream)
                    <div class="text-xs text-slate-300 bg-red-950/40 border border-red-500/40 p-3.5 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-red-400 block">Supporting Live Stream:</span>
                            <span class="font-bold text-white">{{ $donation->liveStream->title }}</span>
                        </div>
                        <a href="{{ route('live.show', $donation->liveStream->slug) }}" class="text-red-300 hover:text-white underline font-semibold shrink-0 ml-3">
                            &larr; Back to Stream
                        </a>
                    </div>
                @else
                    <div class="text-xs text-slate-300 bg-emerald-950/30 border border-emerald-500/40 p-3.5 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-emerald-400 block">Supporting:</span>
                            <span class="font-bold text-white">Kisii TV Independent Journalism Fund</span>
                        </div>
                        <a href="{{ route('donations.index') }}" class="text-emerald-300 hover:text-white underline font-semibold shrink-0 ml-3">
                            &larr; Back to Portal
                        </a>
                    </div>
                @endif

                <!-- Gateway Specific Interactive Checkout Modules -->
                @if($donation->gateway_code === 'mpesa')
                    <div class="bg-emerald-950/30 border border-emerald-500/40 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-400 flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Lipa na M-Pesa STK Push / Paybill</span>
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Sandbox / Test Mode' : 'Live Daraja Online' }}
                            </span>
                        </div>

                        <!-- STK Push Trigger -->
                        <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-3">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300">
                                Send Instant STK Push Prompt to Handset:
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="text" x-model="phone" placeholder="e.g. 0712345678"
                                    class="flex-grow bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded px-3 py-2 text-xs font-mono text-white focus:outline-none">
                                <button type="button" @click="triggerStkPush()" :disabled="stkLoading"
                                    class="py-2 px-4 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider transition cursor-pointer disabled:opacity-50 flex items-center justify-center space-x-2 shrink-0">
                                    <svg x-show="stkLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Prompt Phone Now</span>
                                </button>
                            </div>

                            <div x-show="stkSent" x-cloak
                                :class="stkSuccess ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-200' : 'bg-red-950/80 border-red-500/50 text-red-200'"
                                class="p-3 rounded border text-xs leading-relaxed"
                                x-text="stkMessage">
                            </div>
                        </div>

                        <!-- Manual Paybill Information -->
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Or Pay Manually via Paybill:</div>
                            <div class="grid grid-cols-2 gap-3 bg-slate-950 p-3 rounded border border-slate-800 text-xs">
                                <div>
                                    <span class="text-[10px] uppercase text-slate-400 block">Paybill / Till Shortcode</span>
                                    <span class="font-mono font-black text-emerald-400 text-sm">{{ $donation->paymentGateway?->shortcode_or_merchant_id ?: '174379' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase text-slate-400 block">Account Reference</span>
                                    <span class="font-mono font-black text-white text-sm">{{ $donation->paymentGateway?->account_reference ?: 'KISIITV' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($donation->gateway_code === 'paystack')
                    <div class="bg-blue-950/30 border border-blue-500/40 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-blue-400">Paystack Secure Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-500/20 text-blue-300">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Test Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Click below to open Paystack secure payment window. Supports Visa, Mastercard, Apple Pay, and M-Pesa.
                        </p>
                        <button type="button" @click="payWithPaystack()"
                            class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition cursor-pointer flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            <span>Pay with Paystack &rarr;</span>
                        </button>
                    </div>

                @elseif($donation->gateway_code === 'flutterwave')
                    <div class="bg-amber-950/30 border border-amber-500/40 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-amber-400">Flutterwave Multi-Channel Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/20 text-amber-200">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Test Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Accepting cards, M-Pesa, Airtel Money, and bank transfers throughout Africa.
                        </p>
                        <button type="button" @click="payWithFlutterwave()"
                            class="w-full py-3 px-4 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition cursor-pointer flex items-center justify-center space-x-2">
                            <span>Pay with Flutterwave &rarr;</span>
                        </button>
                    </div>

                @elseif($donation->gateway_code === 'paypal')
                    <div class="bg-indigo-950/30 border border-indigo-500/40 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-indigo-300">PayPal International Gateway</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-200">
                                {{ $donation->paymentGateway?->is_test_mode ? 'Sandbox Mode' : 'Live Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            For international contributors and Kenyans in the diaspora.
                        </p>
                    </div>
                @endif

                <!-- Payment Confirmation / Reference Form -->
                <form id="payment_complete_form" method="POST" action="{{ route('donations.complete', $donation->transaction_reference) }}" class="space-y-4 pt-3 border-t border-slate-800">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Transaction / Receipt Reference
                        </label>
                        <input id="external_reference_input" type="text" name="external_reference" x-model="externalRef"
                            placeholder="{{ $donation->gateway_code === 'mpesa' ? 'e.g. QKA82M9X1P (M-Pesa Receipt Code)' : 'Gateway reference or auto-populated on checkout' }}"
                            class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3.5 py-2.5 text-xs text-white focus:outline-none">
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="{{ $donation->campaign ? route('campaigns.show', $donation->campaign->slug) : ($donation->liveStream ? route('live.show', $donation->liveStream->slug) : route('donations.index')) }}"
                            class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                            Cancel &amp; Return
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider shadow-lg transition cursor-pointer">
                            Confirm &amp; Complete {{ ucfirst($donation->gateway_code) }} Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@if($donation->gateway_code === 'paystack')
    <script src="https://js.paystack.co/v1/inline.js"></script>
@elseif($donation->gateway_code === 'flutterwave')
    <script src="https://checkout.flutterwave.com/v3.js"></script>
@endif
@endsection
