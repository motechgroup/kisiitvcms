@extends('layouts.news')

@section('title', 'Support Kisii TV — Power Independent Journalism in Kenya')

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-10 sm:py-14 border-b-4 border-[#d9252a]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Flash Success Notification -->
        @if(session('success'))
            <div class="mb-8 p-4 rounded-xl bg-emerald-950/90 border border-emerald-500/50 text-emerald-100 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs sm:text-sm font-semibold leading-relaxed">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-8 p-4 rounded-xl bg-red-950/90 border border-red-500/50 text-red-100 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs sm:text-sm font-semibold leading-relaxed">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Portal Header -->
        <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-12">
            <div class="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-black uppercase tracking-widest px-3.5 py-1 rounded-full mb-3 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Kisii TV Independent Journalism Fund</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black font-headline tracking-tight text-white leading-tight">
                Support <span class="text-[#d9252a]">Kisii TV</span> Newsroom
            </h1>
            <p class="mt-4 text-sm sm:text-base text-slate-300 leading-relaxed max-w-2xl mx-auto">
                Your contribution directly funds fearless investigative reports, live digital broadcast trucks, and community-centered stories across Kisii, Nyamira, and the greater Kenyan nation.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start"
            x-data="{
                amount: '{{ old('amount', '500') }}',
                gateway: '{{ old('gateway_code', $activeGateways->first()?->code ?? 'mpesa') }}'
            }">

            <!-- Left Form Column (7 Cols) -->
            <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-[#0a1c3d] px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-emerald-400 fill-current" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span class="font-bold text-sm uppercase tracking-wider text-white">Make Your Contribution</span>
                    </div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                        100% Direct Impact
                    </span>
                </div>

                <div class="p-6 sm:p-7 space-y-6">
                    @if($errors->any())
                        <div class="bg-red-950/90 border-l-4 border-red-500 text-red-100 p-3.5 rounded text-xs space-y-1">
                            <div class="font-bold uppercase tracking-wider text-red-300">Please review your submission:</div>
                            <ul class="list-disc list-inside text-red-200">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($activeGateways->isNotEmpty())
                        <form method="POST" action="{{ route('donations.store') }}" class="space-y-6">
                            @csrf

                            <!-- Step 1: Contribution Amount -->
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-2">
                                    1. Select or Enter Amount (KES / USD)
                                </label>
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-3">
                                    @foreach([200, 500, 1000, 2500, 5000, 10000] as $preset)
                                        <button type="button"
                                            @click="amount = '{{ $preset }}'"
                                            :class="amount == '{{ $preset }}' ? 'bg-emerald-600 text-white border-emerald-400 shadow-md ring-2 ring-emerald-500/50' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-slate-600'"
                                            class="py-2.5 px-2 text-xs font-black rounded-lg border transition text-center cursor-pointer">
                                            {{ number_format($preset) }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-3 text-slate-500 text-sm font-bold font-mono">KES</span>
                                    <input type="number" name="amount" x-model="amount" min="10" step="any" required
                                        placeholder="Enter custom amount..."
                                        class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg pl-14 pr-4 py-3 text-sm font-bold text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                </div>
                            </div>

                            <!-- Step 2: Payment Gateway Selection -->
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-300 mb-2">
                                    2. Choose Preferred Payment Method
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach($activeGateways as $gw)
                                        <label @click="gateway = '{{ $gw->code }}'"
                                            :class="gateway === '{{ $gw->code }}' ? 'border-emerald-500 bg-emerald-950/40 text-white shadow-md' : 'border-slate-800 bg-slate-950/60 text-slate-300 hover:border-slate-700'"
                                            class="border-2 rounded-xl p-3 cursor-pointer transition flex flex-col justify-between">
                                            <div class="flex items-center justify-between">
                                                <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="gateway" class="sr-only">
                                                <span class="text-xs font-black uppercase tracking-wide">
                                                    {{ $gw->code === 'mpesa' ? 'Lipa na M-Pesa' : ucfirst($gw->code) }}
                                                </span>
                                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-emerald-300 font-bold">
                                                    {{ $gw->currency }}
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 line-clamp-1">
                                                {{ $gw->provider_label ?? $gw->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Step 3: Supporter Details -->
                            <div class="space-y-4 pt-2 border-t border-slate-800">
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-300">
                                    3. Your Details
                                </label>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Full Name *</label>
                                    <input type="text" name="donor_name" value="{{ old('donor_name', auth()->user()?->name) }}" required
                                        placeholder="e.g. Adv. Moraa Nyaboke"
                                        class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3.5 py-2.5 text-xs text-white focus:outline-none">
                                </div>

                                <div x-show="gateway === 'mpesa'" x-cloak>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-emerald-400 mb-1">
                                        M-Pesa Mobile Number * (For Instant STK Push)
                                    </label>
                                    <input type="text" name="donor_phone" value="{{ old('donor_phone') }}"
                                        placeholder="e.g. 0712345678 or 254712345678"
                                        class="w-full bg-slate-950 border border-emerald-500/60 focus:border-emerald-400 rounded-lg px-3.5 py-2.5 text-xs text-white focus:outline-none">
                                    <span class="text-[10px] text-slate-400 mt-1 block">
                                        You will receive an instant Safaricom PIN prompt on your handset.
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email Address (Optional, for receipt)</label>
                                    <input type="email" name="donor_email" value="{{ old('donor_email', auth()->user()?->email) }}"
                                        placeholder="you@example.com"
                                        class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3.5 py-2.5 text-xs text-white focus:outline-none">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Words of Support (Optional)</label>
                                    <textarea name="message" rows="2" placeholder="Leave a brief message or story idea for our editorial desk..."
                                        class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg px-3.5 py-2.5 text-xs text-white focus:outline-none">{{ old('message') }}</textarea>
                                </div>

                                <label class="flex items-center space-x-2.5 text-xs text-slate-300 cursor-pointer pt-1">
                                    <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                                        class="rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0 w-4 h-4">
                                    <span>Make my contribution anonymous on the public supporters roster</span>
                                </label>
                            </div>

                            <button type="submit"
                                class="w-full py-3.5 px-6 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-xl transition cursor-pointer flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <span>Proceed to Complete Contribution &rarr;</span>
                            </button>
                        </form>
                    @else
                        <div class="p-6 rounded-xl bg-slate-950 border border-slate-800 text-center text-xs text-slate-400">
                            Donation gateways are temporarily undergoing scheduled maintenance. Please check back shortly.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Direct Paybill Info & Mission Impact (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Safaricom M-Pesa Quick Paybill Card -->
                <div class="bg-gradient-to-br from-slate-900 to-[#0a1c3d] border-2 border-emerald-500/50 rounded-2xl p-6 text-white shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Direct Paybill Desk</span>
                        <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-mono px-2 py-0.5 rounded border border-emerald-500/30">Instant</span>
                    </div>

                    <div>
                        <h3 class="text-base font-black font-headline text-white">Manual Lipa na M-Pesa Instructions</h3>
                        <p class="text-xs text-slate-300 mt-1">If you prefer manual payment on your phone's M-Pesa SIM Toolkit menu:</p>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 grid grid-cols-2 gap-4 text-xs font-mono">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Business / Paybill</span>
                            <span class="text-lg font-black text-emerald-400">174379</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Account Number</span>
                            <span class="text-lg font-black text-white">KISIITV</span>
                        </div>
                    </div>

                    <ol class="list-decimal list-inside text-xs text-slate-300 space-y-1 font-medium leading-relaxed">
                        <li>Go to <strong>M-Pesa</strong> &rarr; <strong>Lipa na M-Pesa</strong> &rarr; <strong>Paybill</strong></li>
                        <li>Enter Business Number <strong>174379</strong></li>
                        <li>Enter Account Number <strong>KISIITV</strong></li>
                        <li>Enter your preferred amount &amp; your M-Pesa PIN</li>
                    </ol>
                </div>

                <!-- Three Impact Pillars -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-300 border-b border-slate-800 pb-2">
                        Why Your Support Matters
                    </h3>

                    <div class="space-y-4 text-xs">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-[#d9252a]/20 text-[#d9252a] flex items-center justify-center shrink-0 font-bold">
                                01
                            </div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Fearless County Investigations</h4>
                                <p class="text-slate-400 mt-0.5 leading-relaxed">
                                    Holding county leadership and public institutions accountable through non-partisan, evidence-backed investigative reporting.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-[#1b52bc]/20 text-blue-400 flex items-center justify-center shrink-0 font-bold">
                                02
                            </div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Live Broadcasts Everywhere</h4>
                                <p class="text-slate-400 mt-0.5 leading-relaxed">
                                    Powering live stream broadcasts of community events, public barazas, elections, and cultural festivals direct to your phone.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 font-bold">
                                03
                            </div>
                            <div>
                                <h4 class="font-bold text-white text-sm">100% Free Public News Access</h4>
                                <p class="text-slate-400 mt-0.5 leading-relaxed">
                                    Keeping public-interest news free and open to everyone without paywalls or subscriptions.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editorial Hotline & Office -->
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 space-y-1">
                    <div class="font-bold text-slate-300 uppercase tracking-wider text-[10px]">Newsroom Administration Desk</div>
                    <div>Sansora Complex, Kisii Town &amp; Nairobi Media Center</div>
                    <div>Hotline / WhatsApp: <a href="https://wa.me/254726547363" class="text-emerald-400 hover:underline font-bold">+254 726 547 363</a></div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
