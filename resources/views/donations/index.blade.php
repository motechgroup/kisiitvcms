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
                        <x-donation-form :gateways="$activeGateways" />
                    @else
                        <div class="p-6 rounded-xl bg-slate-950 border border-slate-800 text-center text-xs text-slate-400">
                            Donation gateways are temporarily undergoing scheduled maintenance. Please check back shortly.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Mission Impact (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">

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
                @php
                    $officeAddress = \App\Models\SiteSetting::get('newsroom_address', 'Sansora Complex, Kisii Town & Nairobi Media Center');
                    $officePhone = \App\Models\SiteSetting::get('contact_phone', '+254 726 547 363');
                    $officeEmail = \App\Models\SiteSetting::get('contact_email', 'kisiitelevision@gmail.com');
                    $whatsappHref = \App\Models\SiteSetting::get('social_whatsapp', 'https://wa.me/254726547363');
                @endphp
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 space-y-1">
                    <div class="font-bold text-slate-300 uppercase tracking-wider text-[10px]">Newsroom Administration Desk</div>
                    <div>{{ $officeAddress }}</div>
                    <div>Desk Email: <a href="mailto:{{ $officeEmail }}" class="text-blue-400 hover:underline font-semibold">{{ $officeEmail }}</a></div>
                    <div>Hotline / WhatsApp: <a href="{{ $whatsappHref }}" target="_blank" rel="noopener" class="text-emerald-400 hover:underline font-bold">{{ $officePhone }}</a></div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
