@extends('layouts.news')

@section('title', 'Complete Your Contribution — Kisii TV')

@section('content')
<div class="bg-slate-950 text-white py-12 border-b-4 border-[#d9252a]"
    x-data="{
        stkLoading: false,
        stkSent: {{ session('stk_result') ? 'true' : 'false' }},
        stkSuccess: {{ session('stk_result.success', true) ? 'true' : 'false' }},
        stkMessage: {{ Js::from(session('stk_result.message', 'STK Push prompt sent to ' . ($donation->donor_phone ?: 'your phone') . '. Enter your M-Pesa PIN to complete.')) }},
        pollingTimer: null,
        showReceiptInput: false,
        phone: '{{ $donation->donor_phone }}',
        externalRef: '{{ old('external_reference', $donation->external_reference) }}',

        init() {
            @if($donation->gateway_code === 'mpesa' && $donation->status === 'pending')
                this.startPolling();
            @elseif($donation->gateway_code === 'paystack' && $donation->status === 'pending' && !empty($donation->paymentGateway?->public_key))
                this.$nextTick(() => this.payWithPaystack());
            @elseif($donation->gateway_code === 'flutterwave' && $donation->status === 'pending' && !empty($donation->paymentGateway?->public_key))
                this.$nextTick(() => this.payWithFlutterwave());
            @endif
        },

        triggerStkPush() {
            this.stkLoading = true;
            this.stkMessage = 'Sending M-Pesa STK Push to your handset...';

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
            .catch(() => {
                this.stkLoading = false;
                this.stkSent = true;
                this.stkSuccess = false;
                this.stkMessage = 'Could not resend STK prompt. Please use Paybill {{ $donation->paymentGateway?->shortcode_or_merchant_id ?: '174379' }} Account {{ $donation->paymentGateway?->account_reference ?: 'KISIITV' }}.';
            });
        },

        startPolling() {
            if (this.pollingTimer) clearInterval(this.pollingTimer);
            let attempts = 0;
            this.pollingTimer = setInterval(() => {
                attempts++;
                if (attempts > 30) {
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
            }, 2500);
        },

        payWithPaystack() {
            @if($donation->paymentGateway && !empty($donation->paymentGateway->public_key))
                let handler = PaystackPop.setup({
                    key: '{{ $donation->paymentGateway->public_key }}',
                    email: '{{ $donation->donor_email ?: "supporter@kisiitv.co.ke" }}',
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
                    }
                });
                handler.openIframe();
            @else
                this.externalRef = 'PSTK-' + Math.random().toString(36).substring(2, 10).toUpperCase();
                $nextTick(() => document.getElementById('payment_complete_form').submit());
            @endif
        },

        payWithFlutterwave() {
            @if($donation->paymentGateway && !empty($donation->paymentGateway->public_key))
                FlutterwaveCheckout({
                    public_key: '{{ $donation->paymentGateway->public_key }}',
                    tx_ref: '{{ $donation->transaction_reference }}',
                    amount: {{ $donation->amount }},
                    currency: '{{ $donation->currency ?: "KES" }}',
                    payment_options: 'card,mobilemoneykenya,ussd',
                    customer: {
                        email: '{{ $donation->donor_email ?: "supporter@kisiitv.co.ke" }}',
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
                $nextTick(() => document.getElementById('payment_complete_form').submit());
            @endif
        }
    }">

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-950/90 border border-red-500/50 text-red-100 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-[#0a1c3d] px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 block">
                        {{ strtoupper($donation->gateway_code) }} Instant Checkout
                    </span>
                    <h1 class="text-lg sm:text-xl font-black font-headline text-white mt-0.5">
                        {{ $donation->currency }} {{ number_format((float) $donation->amount, 2) }}
                    </h1>
                </div>
                <span class="px-3 py-1 rounded bg-slate-950 border border-slate-700 font-mono text-xs text-slate-300">
                    {{ $donation->transaction_reference }}
                </span>
            </div>

            <div class="p-6 space-y-5">

                <!-- Attribution Strip -->
                @if($donation->campaign)
                    <div class="text-xs text-slate-300 bg-purple-950/40 border border-purple-500/40 p-3 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-purple-400 block">Campaign:</span>
                            <span class="font-bold text-white">{{ $donation->campaign->title }}</span>
                        </div>
                        <a href="{{ route('campaigns.show', $donation->campaign->slug) }}" class="text-purple-300 hover:text-white underline font-semibold shrink-0 ml-3">
                            &larr; Back
                        </a>
                    </div>
                @elseif($donation->liveStream)
                    <div class="text-xs text-slate-300 bg-red-950/40 border border-red-500/40 p-3 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-red-400 block">Live Stream:</span>
                            <span class="font-bold text-white">{{ $donation->liveStream->title }}</span>
                        </div>
                        <a href="{{ route('live.show', $donation->liveStream->slug) }}" class="text-red-300 hover:text-white underline font-semibold shrink-0 ml-3">
                            &larr; Back
                        </a>
                    </div>
                @endif

                @if($donation->gateway_code === 'mpesa')
                    <!-- Streamlined M-Pesa STK Waiting Card -->
                    <div class="bg-emerald-950/30 border border-emerald-500/40 rounded-xl p-5 space-y-4 text-center">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto animate-pulse">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-base font-black text-white">Check Your Phone ({{ $donation->donor_phone }})</h2>
                            <p class="text-xs text-emerald-200 mt-1 leading-relaxed" x-text="stkMessage"></p>
                        </div>

                        <div class="flex items-center justify-center gap-2 pt-1">
                            <input type="tel" x-model="phone" placeholder="07XXXXXXXX"
                                class="w-40 bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs font-mono text-white text-center focus:outline-none">
                            <button type="button" @click="triggerStkPush()" :disabled="stkLoading"
                                class="py-2 px-4 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-600 text-white font-bold text-xs uppercase tracking-wider transition cursor-pointer disabled:opacity-50">
                                <span x-text="stkLoading ? 'Sending...' : 'Resend Prompt'"></span>
                            </button>
                        </div>

                        <!-- Manual Paybill Fallback -->
                        <div class="grid grid-cols-2 gap-3 bg-slate-950 p-3 rounded-lg border border-slate-800 text-xs text-left">
                            <div>
                                <span class="text-[10px] uppercase text-slate-400 block">Paybill / Till</span>
                                <span class="font-mono font-black text-emerald-400 text-sm">{{ $donation->paymentGateway?->shortcode_or_merchant_id ?: '174379' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase text-slate-400 block">Account</span>
                                <span class="font-mono font-black text-white text-sm">{{ $donation->paymentGateway?->account_reference ?: 'KISIITV' }}</span>
                            </div>
                        </div>
                    </div>

                @elseif($donation->gateway_code === 'paystack')
                    <div class="bg-blue-950/30 border border-blue-500/40 rounded-xl p-5 space-y-3 text-center">
                        @if(!empty($donation->paymentGateway?->public_key))
                            <h2 class="text-base font-black text-white">Launching Paystack Secure Checkout...</h2>
                            <p class="text-xs text-slate-300">
                                Complete your payment in the Paystack window. If it did not open, click below:
                            </p>
                            <button type="button" @click="payWithPaystack()"
                                class="py-2.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider shadow transition cursor-pointer">
                                Re-open Paystack Window &rarr;
                            </button>
                        @else
                            <h2 class="text-base font-black text-white">Paystack Sandbox Mode</h2>
                            <p class="text-xs text-amber-200 leading-relaxed">
                                Paystack Public &amp; Secret keys are not configured yet in <strong>Admin &rarr; Payment Gateways</strong>. Once added, donors are redirected straight to Paystack's live checkout page.
                            </p>
                        @endif
                    </div>

                @elseif($donation->gateway_code === 'flutterwave')
                    <div class="bg-amber-950/30 border border-amber-500/40 rounded-xl p-5 space-y-3 text-center">
                        @if(!empty($donation->paymentGateway?->public_key))
                            <h2 class="text-base font-black text-white">Launching Flutterwave Secure Checkout...</h2>
                            <p class="text-xs text-slate-300">
                                Complete your payment in the Flutterwave window. If it did not open, click below:
                            </p>
                            <button type="button" @click="payWithFlutterwave()"
                                class="py-2.5 px-5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs uppercase tracking-wider shadow transition cursor-pointer">
                                Re-open Flutterwave Window &rarr;
                            </button>
                        @else
                            <h2 class="text-base font-black text-white">Flutterwave Sandbox Mode</h2>
                            <p class="text-xs text-amber-200 leading-relaxed">
                                Flutterwave Public &amp; Secret keys are not configured yet in <strong>Admin &rarr; Payment Gateways</strong>. Once added, donors are redirected straight to Flutterwave's live checkout page.
                            </p>
                        @endif
                    </div>

                @elseif($donation->gateway_code === 'paypal')
                    <div class="bg-indigo-950/30 border border-indigo-500/40 rounded-xl p-5 space-y-4 text-center">
                        <h2 class="text-base font-black text-white">PayPal International Checkout</h2>
                        <p class="text-xs text-slate-300">
                            Complete your international contribution via PayPal Wallet or Card.
                        </p>
                    </div>
                @endif

                <!-- Minimal 1-Click Completion Form -->
                <form id="payment_complete_form" method="POST" action="{{ route('donations.complete', $donation->transaction_reference) }}" class="space-y-3 pt-2 border-t border-slate-800">
                    @csrf

                    <div>
                        <button type="button" @click="showReceiptInput = !showReceiptInput"
                            class="text-[11px] text-slate-400 hover:text-slate-200 underline cursor-pointer">
                            <span x-text="showReceiptInput ? 'Hide receipt code' : 'Have an M-Pesa / receipt code? (Optional)'"></span>
                        </button>
                        <div x-show="showReceiptInput" x-cloak class="mt-2">
                            <input id="external_reference_input" type="text" name="external_reference" x-model="externalRef"
                                placeholder="e.g. QKA82M9X1P"
                                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3.5 py-2 text-xs text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="{{ $donation->campaign ? route('campaigns.show', $donation->campaign->slug) : ($donation->liveStream ? route('live.show', $donation->liveStream->slug) : route('donations.index')) }}"
                            class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                            Cancel
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider shadow-lg transition cursor-pointer">
                            {{ $donation->gateway_code === 'mpesa' ? "I've Paid — Confirm Now" : 'Confirm & Complete Payment' }}
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
