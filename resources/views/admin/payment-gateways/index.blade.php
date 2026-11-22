@extends('layouts.admin')

@section('title', 'Payment Gateways Management')
@section('page_title', 'Payment Gateways Manager (M-Pesa, Paystack, PayPal & Flutterwave)')

@section('content')
<div class="space-y-8">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Payment Gateways &amp; Checkout Credentials</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Configure API keys, Paybill/Till numbers, currencies, and enable or disable <strong>M-Pesa</strong>, <strong>Paystack</strong>, <strong>PayPal</strong>, and <strong>Flutterwave</strong>.
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

    <!-- Gateways Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($gateways as $gateway)
            <div class="bg-white border-2 {{ $gateway->is_active ? 'border-slate-200' : 'border-slate-200 opacity-80' }} shadow-sm rounded-lg overflow-hidden flex flex-col justify-between">
                <!-- Card Header -->
                <div class="bg-[#0a1c3d] text-white px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-black uppercase tracking-wider font-headline">{{ $gateway->name }}</span>
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-blue-300 font-mono text-[10px] uppercase">{{ $gateway->code }}</span>
                        </div>
                        <p class="text-[11px] text-slate-300 mt-0.5">{{ $gateway->description }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Mode Toggle -->
                        <form method="POST" action="{{ route('admin.payment-gateways.toggle-mode', $gateway) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider cursor-pointer transition {{ $gateway->is_test_mode ? 'bg-amber-400 text-slate-950 hover:bg-amber-300' : 'bg-blue-500 text-white hover:bg-blue-400' }}">
                                {{ $gateway->is_test_mode ? 'Sandbox / Test' : 'Live Production' }}
                            </button>
                        </form>

                        <!-- Active Toggle -->
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

                <!-- Configuration Form -->
                <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway) }}" class="p-5 space-y-4 text-xs">
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

                    <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-100">
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

                        <div class="flex items-center space-x-3">
                            <span class="text-[11px] text-slate-400 font-mono">
                                {{ $gateway->completed_donations_count ?? 0 }} paid ({{ $gateway->currency }} {{ number_format((float) ($gateway->completed_donations_sum ?? 0), 0) }})
                            </span>
                            <button type="submit" class="px-4 py-2 bg-[#1b52bc] hover:bg-blue-800 text-white font-bold uppercase tracking-wider rounded transition cursor-pointer">
                                Save {{ ucfirst($gateway->code) }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
    </div>

</div>
@endsection
