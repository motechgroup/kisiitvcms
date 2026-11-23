@extends('layouts.admin')

@section('title', 'Payment Gateways Management')
@section('page_title', 'Payment Gateways Manager (M-Pesa, Paystack, PayPal & Flutterwave)')

@section('content')
<div class="space-y-8" x-data="{ openModal: null }">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Payment Gateways &amp; Checkout Credentials</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Click any payment gateway card below to open its credentials modal and configure API keys, Paybill/Till numbers, currencies, or status for <strong>M-Pesa</strong>, <strong>Paystack</strong>, <strong>PayPal</strong>, and <strong>Flutterwave</strong>.
            </p>
        </div>
        <div class="flex items-center space-x-2.5 shrink-0">
            <a href="{{ route('admin.streams.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold uppercase tracking-wider rounded transition">
                &larr; Live Streams
            </a>
            <a href="{{ route('admin.donations.index') }}" class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                View Donations Ledger
            </a>
        </div>
    </div>

    <!-- Payment Gateway Summary Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($gateways as $gateway)
            <div @click="openModal = '{{ $gateway->code }}'"
                class="group bg-white border-2 {{ $gateway->is_active ? 'border-slate-200 hover:border-[#1b52bc]' : 'border-slate-200 opacity-80 hover:border-slate-400' }} shadow-sm hover:shadow-lg rounded-xl overflow-hidden flex flex-col justify-between transition cursor-pointer">

                <!-- Card Top Banner -->
                <div class="bg-[#0a1c3d] text-white px-5 py-4 flex items-start justify-between gap-3">
                    <div class="flex items-start space-x-3">
                        @if($gwLogo = $gateway->logoUrl())
                            <img src="{{ $gwLogo }}" alt="{{ $gateway->name }}" class="h-10 w-16 object-cover rounded-lg border border-slate-700 shadow-sm shrink-0">
                        @endif
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-black uppercase tracking-wider font-headline group-hover:text-blue-300 transition">
                                    {{ $gateway->name }}
                                </span>
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-blue-300 font-mono text-[10px] uppercase">
                                    {{ $gateway->code }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-300 mt-1 line-clamp-2">
                                {{ $gateway->description }}
                            </p>
                        </div>
                    </div>

                    <!-- Quick Status & Mode Badges (Stop click propagation so quick-toggles work directly) -->
                    <div class="flex items-center space-x-1.5 shrink-0" @click.stop>
                        <form method="POST" action="{{ route('admin.payment-gateways.toggle-mode', $gateway) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider cursor-pointer transition {{ $gateway->is_test_mode ? 'bg-amber-400 text-slate-950 hover:bg-amber-300' : 'bg-blue-500 text-white hover:bg-blue-400' }}">
                                {{ $gateway->is_test_mode ? 'Sandbox / Test' : 'Live Mode' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.payment-gateways.toggle-active', $gateway) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider cursor-pointer transition {{ $gateway->is_active ? 'bg-emerald-500 text-slate-950 hover:bg-red-500 hover:text-white' : 'bg-slate-700 text-slate-300 hover:bg-emerald-500 hover:text-slate-950' }}">
                                {{ $gateway->is_active ? '✓ Enabled' : 'Disabled' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card Body: Key Telemetry & Summary -->
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-3 gap-3 bg-slate-50 border border-slate-200/80 rounded-lg p-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Currency</span>
                            <span class="font-mono font-black text-slate-900 text-sm">{{ $gateway->currency }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">
                                {{ $gateway->code === 'mpesa' ? 'Paybill / Till' : 'Credentials' }}
                            </span>
                            <span class="font-mono font-bold text-slate-800 text-xs truncate block mt-0.5">
                                @if($gateway->code === 'mpesa')
                                    {{ $gateway->shortcode_or_merchant_id ?: 'Not Set' }}
                                @else
                                    {{ $gateway->public_key ? 'API Keys Saved' : 'Default / Test' }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Processed</span>
                            <span class="font-mono font-bold text-emerald-700 text-xs block mt-0.5">
                                {{ $gateway->currency }} {{ number_format((float) ($gateway->completed_donations_sum ?? 0), 0) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[11px] text-slate-500">
                            {{ $gateway->provider_label }}
                        </span>
                        <button type="button"
                            @click.stop="openModal = '{{ $gateway->code }}'"
                            class="px-3.5 py-2 rounded bg-[#1b52bc] group-hover:bg-blue-800 text-white font-bold text-[11px] uppercase tracking-wider transition flex items-center space-x-1.5 cursor-pointer shadow-sm">
                            <span>Configure {{ ucfirst($gateway->code) }}</span>
                            <span>&rarr;</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- CONFIGURATION MODAL FOR THIS GATEWAY       -->
            <!-- ========================================== -->
            <div x-show="openModal === '{{ $gateway->code }}'" x-cloak style="display: none;"
                @keydown.escape.window="openModal = null"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
                x-transition>

                <div @click.outside="openModal = null"
                    class="bg-white w-full max-w-xl rounded-xl shadow-2xl border border-slate-200 overflow-hidden">

                    <!-- Modal Header -->
                    <div class="bg-[#0a1c3d] text-white px-6 py-4 flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-sm font-black uppercase tracking-wider font-headline">
                                    Configure {{ $gateway->name }}
                                </h3>
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-blue-300 font-mono text-[10px] uppercase">
                                    {{ $gateway->code }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-300 mt-0.5">{{ $gateway->description }}</p>
                        </div>
                        <button type="button" @click="openModal = null"
                            class="text-slate-400 hover:text-white text-xl font-bold leading-none p-1 cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway) }}" class="p-6 space-y-4 text-xs">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-bold uppercase text-slate-700 mb-1">Display Name *</label>
                                <input type="text" name="name" value="{{ $gateway->name }}" required
                                    class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                            </div>
                            <div>
                                <label class="block font-bold uppercase text-slate-700 mb-1">Default Currency *</label>
                                <input type="text" name="currency" value="{{ $gateway->currency }}" required placeholder="KES or USD"
                                    class="w-full border border-slate-300 rounded px-3 py-2 font-mono uppercase focus:outline-none focus:border-[#1b52bc]">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Checkout Label</label>
                            <input type="text" name="provider_label" value="{{ $gateway->provider_label }}"
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                        </div>

                        @if($gateway->code === 'mpesa')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3 rounded border border-slate-200">
                                <div>
                                    <label class="block font-bold uppercase text-slate-700 mb-1">M-Pesa Paybill / Till Shortcode</label>
                                    <input type="text" name="shortcode_or_merchant_id" value="{{ $gateway->shortcode_or_merchant_id }}"
                                        placeholder="e.g. 174379"
                                        class="w-full border border-slate-300 rounded px-3 py-2 font-mono bg-white focus:outline-none focus:border-[#1b52bc]">
                                </div>
                                <div>
                                    <label class="block font-bold uppercase text-slate-700 mb-1">Account Reference</label>
                                    <input type="text" name="account_reference" value="{{ $gateway->account_reference }}"
                                        placeholder="e.g. KISIITV"
                                        class="w-full border border-slate-300 rounded px-3 py-2 font-mono bg-white focus:outline-none focus:border-[#1b52bc]">
                                </div>
                            </div>
                        @else
                            <div>
                                <label class="block font-bold uppercase text-slate-700 mb-1">Merchant ID / Business Email (Optional)</label>
                                <input type="text" name="shortcode_or_merchant_id" value="{{ $gateway->shortcode_or_merchant_id }}"
                                    placeholder="Merchant ID or PayPal Business Email"
                                    class="w-full border border-slate-300 rounded px-3 py-2 font-mono focus:outline-none focus:border-[#1b52bc]">
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold uppercase text-slate-700 mb-1">
                                    {{ $gateway->code === 'mpesa' ? 'Daraja Consumer Key' : ($gateway->code === 'paypal' ? 'PayPal Client ID' : 'Public Key') }}
                                </label>
                                <input type="text" name="public_key" value="{{ $gateway->public_key }}"
                                    placeholder="Enter public / client key..."
                                    class="w-full border border-slate-300 rounded px-3 py-2 font-mono text-[11px] focus:outline-none focus:border-[#1b52bc]">
                            </div>
                            <div>
                                <label class="block font-bold uppercase text-slate-700 mb-1">
                                    {{ $gateway->code === 'mpesa' ? 'Daraja Consumer Secret' : ($gateway->code === 'paypal' ? 'PayPal Client Secret' : 'Secret Key') }}
                                </label>
                                <input type="password" name="secret_key"
                                    placeholder="{{ $gateway->secret_key ? '•••••••••••••••• (Saved)' : 'Enter secret key...' }}"
                                    class="w-full border border-slate-300 rounded px-3 py-2 font-mono text-[11px] focus:outline-none focus:border-[#1b52bc]">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">
                                {{ $gateway->code === 'mpesa' ? 'Daraja Lipa Na M-Pesa Passkey' : 'Webhook Secret / Encryption Key (Optional)' }}
                            </label>
                            <input type="password" name="passkey_or_webhook_secret"
                                placeholder="{{ $gateway->passkey_or_webhook_secret ? '•••••••••••••••• (Saved)' : 'Enter passkey or webhook secret...' }}"
                                class="w-full border border-slate-300 rounded px-3 py-2 font-mono text-[11px] focus:outline-none focus:border-[#1b52bc]">
                        </div>

                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Donor Checkout Instructions</label>
                            <textarea name="instructions" rows="2"
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">{{ $gateway->instructions }}</textarea>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-200">
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ $gateway->is_active ? 'checked' : '' }} class="rounded text-emerald-600">
                                    <span class="font-bold text-slate-700">Gateway Active</span>
                                </label>
                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                    <input type="checkbox" name="is_test_mode" value="1" {{ $gateway->is_test_mode ? 'checked' : '' }} class="rounded text-amber-600">
                                    <span class="font-bold text-slate-700">Sandbox / Test Mode</span>
                                </label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openModal = null"
                                    class="px-4 py-2 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-5 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white font-bold uppercase tracking-wider rounded transition cursor-pointer">
                                    Save {{ ucfirst($gateway->code) }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
