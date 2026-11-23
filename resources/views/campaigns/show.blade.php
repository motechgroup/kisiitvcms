@extends('layouts.news')

@section('title', $campaign->title . ' — Support Kisii TV')

@section('content')
<div class="bg-slate-950 text-white min-h-screen py-10 sm:py-14 border-b-4 border-[#d9252a]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

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

        @php
            $shareUrl = $campaign->shareUrl();
            $shareText = "Support Kisii TV Campaign: " . $campaign->title . " — Powering fearless reporting across Kenya.";
        @endphp

        <!-- Main Campaign Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start"
            x-data="{
                copied: false,
                amount: '{{ old('amount', '500') }}',
                gateway: '{{ old('gateway_code', $activeGateways->first()?->code ?? 'mpesa') }}',

                copyLink() {
                    navigator.clipboard.writeText('{{ $shareUrl }}');
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2500);
                }
            }">

            <!-- Left Column: Story, Progress, & Share Bar (7 Cols) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- Cover Image & Badges -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                    @if($campaign->cover_image)
                        <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-950">
                            <img src="{{ $campaign->cover_image }}" alt="{{ $campaign->title }}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-70"></div>
                            <div class="absolute bottom-3 left-4 flex items-center space-x-2">
                                <span class="bg-purple-600 text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded shadow">
                                    Official Campaign
                                </span>
                                @if($campaign->is_active)
                                    <span class="bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded shadow">
                                        Accepting Donations
                                    </span>
                                @else
                                    <span class="bg-slate-700 text-slate-300 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded shadow">
                                        Campaign Closed
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="p-6 sm:p-7 space-y-5">
                        <h1 class="text-2xl sm:text-3xl font-black font-headline text-white leading-tight">
                            {{ $campaign->title }}
                        </h1>

                        @if($campaign->summary)
                            <p class="text-sm sm:text-base text-slate-300 leading-relaxed font-medium">
                                {{ $campaign->summary }}
                            </p>
                        @endif

                        <!-- Live Funding Progress Box -->
                        <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                                <div>
                                    <span class="text-xs uppercase font-bold text-slate-400 block">Total Raised</span>
                                    <span class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono">
                                        {{ $campaign->currency }} {{ number_format($campaign->totalRaised()) }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs uppercase font-bold text-slate-400 block">Target Goal</span>
                                    <span class="text-lg font-bold text-slate-200 font-mono">
                                        {{ $campaign->currency }} {{ number_format((float) $campaign->target_amount) }}
                                    </span>
                                </div>
                            </div>

                            <div class="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-slate-800">
                                <div class="bg-gradient-to-r from-emerald-500 via-teal-400 to-purple-500 h-3 rounded-full transition-all duration-500"
                                    style="width: {{ $campaign->progressPercentage() }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-300 font-medium pt-1">
                                <span class="text-emerald-400 font-bold">{{ $campaign->progressPercentage() }}% of goal reached</span>
                                <span>{{ $campaign->donorCount() }} Verified Contributions</span>
                            </div>
                        </div>

                        <!-- 1-Click Share Bar (WhatsApp, Facebook, X, Copy Link) -->
                        <div class="bg-slate-950/70 border border-slate-800 p-4 rounded-xl space-y-2.5">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Share Campaign Link:</span>
                                <span class="text-purple-400 text-[10px]">Spread the word!</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Direct Copy Link Button -->
                                <button type="button" @click="copyLink()"
                                    class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider border border-slate-700 flex items-center space-x-1.5 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Direct Link'"></span>
                                </button>

                                <!-- WhatsApp -->
                                <a href="https://api.whatsapp.com/send?text={{ urlencode($shareText . ' ' . $shareUrl) }}"
                                    target="_blank" rel="noopener"
                                    class="py-2 px-3 rounded-lg bg-emerald-600/20 text-emerald-300 hover:bg-emerald-600 hover:text-white border border-emerald-500/40 text-xs font-bold transition flex items-center space-x-1.5">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    <span>WhatsApp</span>
                                </a>

                                <!-- Facebook -->
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                    target="_blank" rel="noopener"
                                    class="py-2 px-3 rounded-lg bg-blue-600/20 text-blue-300 hover:bg-blue-600 hover:text-white border border-blue-500/40 text-xs font-bold transition flex items-center space-x-1.5">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    <span>Facebook</span>
                                </a>

                                <!-- X -->
                                <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}&via=kisiitv"
                                    target="_blank" rel="noopener"
                                    class="py-2 px-3 rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 text-xs font-bold transition flex items-center space-x-1.5">
                                    <span class="font-bold">𝕏</span>
                                    <span>Share</span>
                                </a>
                            </div>
                        </div>

                        <!-- Full Campaign Story Narrative -->
                        @if($campaign->description)
                            <div class="pt-4 border-t border-slate-800 space-y-3">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-300">About This Project</h3>
                                <div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3 whitespace-pre-line">
                                    {{ $campaign->description }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Supporters Wall -->
                @if($recentDonations->isNotEmpty())
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-300">
                                Recent Campaign Supporters
                            </h3>
                            <span class="text-[11px] text-emerald-400 font-bold font-mono">
                                {{ $campaign->donorCount() }} Backers
                            </span>
                        </div>

                        <div class="divide-y divide-slate-800">
                            @foreach($recentDonations as $d)
                                <div class="py-3 flex items-start justify-between gap-3 text-xs">
                                    <div class="space-y-0.5">
                                        <div class="font-bold text-white flex items-center space-x-1.5">
                                            <span>{{ $d->displayName() }}</span>
                                            <span class="text-[9px] font-mono px-1 rounded bg-slate-800 text-slate-400 uppercase">{{ $d->gateway_code }}</span>
                                        </div>
                                        @if($d->message)
                                            <p class="text-[11px] text-slate-400 italic">"{{ $d->message }}"</p>
                                        @endif
                                        <span class="text-[10px] text-slate-500 block">{{ $d->paid_at?->diffForHumans() }}</span>
                                    </div>
                                    <span class="font-black text-emerald-400 font-mono text-sm shrink-0">
                                        {{ $d->currency }} {{ number_format((float) $d->amount) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            <!-- Right Column: Direct Donation Form (5 Cols) -->
            <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden sticky top-6">
                <div class="bg-[#0a1c3d] px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-emerald-400 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span class="font-bold text-sm uppercase tracking-wider text-white">Contribute to Campaign</span>
                    </div>
                    <span class="text-[10px] font-mono uppercase font-bold text-emerald-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                        Goal: {{ number_format((float) $campaign->target_amount) }}
                    </span>
                </div>

                <div class="p-6 space-y-6">
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

                    @if($campaign->is_active && $activeGateways->isNotEmpty())
                        <x-donation-form
                            :gateways="$activeGateways"
                            :campaign-id="$campaign->id"
                            :currency="$campaign->currency"
                            :presets="[500, 1000, 2500, 5000]"
                        />
                    @else
                        <div class="p-6 rounded-xl bg-slate-950 border border-slate-800 text-center text-xs text-slate-400">
                            This campaign is currently closed to new contributions. Thank you to everyone who contributed!
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
