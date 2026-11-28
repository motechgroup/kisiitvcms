@extends('layouts.news')

@section('content')
@php
    $currency = $liveStream->sod_currency ?: 'KES';
    $price = (float) $liveStream->sod_price;
    $formattedPrice = number_format($price, 0);
    $defaultGateway = old('gateway_code', $activeGateways->first()?->code ?? 'paystack');
@endphp

<div class="bg-slate-950 text-white border-b-4 border-[#d9252a]"
    x-data="{
        paywallOpen: {{ (! $hasAccess && ($errors->any() || session('error'))) ? 'true' : 'false' }},
        gateway: '{{ $defaultGateway }}'
    }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-950/90 border-l-4 border-emerald-400 text-emerald-100 p-4 rounded shadow-lg flex items-center space-x-2.5 text-xs sm:text-sm">
                <span class="bg-emerald-500 text-slate-950 font-black px-2 py-0.5 rounded text-[10px] uppercase">Unlocked</span>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-950/90 border-l-4 border-red-500 text-red-100 p-4 rounded shadow-lg flex items-center space-x-2.5 text-xs sm:text-sm">
                <span class="bg-red-600 text-white font-black px-2 py-0.5 rounded text-[10px] uppercase">Access Notice</span>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Studio Top Bar (No Donate Button on SOD Streams) -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 mb-5 border-b border-slate-800">
            <div class="flex items-center space-x-2.5">
                <span class="relative flex h-3 w-3 shrink-0">
                    @if($liveStream->status === 'live')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-[#d9252a]"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    @endif
                </span>
                <span class="text-xs font-black uppercase tracking-widest text-[#d9252a]">Kisii TV Streaming On Demand</span>
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-black uppercase tracking-wider">
                    Pay-Per-View
                </span>
            </div>

            @if(! $hasAccess)
                <button type="button" @click="paywallOpen = true"
                    class="px-4 py-2 rounded-lg bg-[#d9252a] hover:bg-red-600 text-white font-black uppercase tracking-wider text-xs transition shadow-lg shadow-red-600/25 flex items-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <span>Play / Stream Now · {{ $price > 0 ? $currency.' '.$formattedPrice : 'Free' }}</span>
                </button>
            @elseif($activePass)
                <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-bold">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Paid Access Active · Locked to This Device</span>
                </div>
            @elseif($isStaffPreview)
                <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-bold">
                    <span>Admin Staff Preview</span>
                </div>
            @endif
        </div>

        <!-- Main Player / Paywall Cinema Stage -->
        <div class="space-y-5 sm:space-y-6">
            <div class="w-full bg-black border border-slate-800 sm:border-2 shadow-2xl overflow-hidden rounded-lg">
                <div class="relative w-full aspect-video max-h-[80vh] mx-auto" style="aspect-ratio: 16 / 9;">
                    @if($hasAccess)
                        @if($liveStream->usesTwitchJsPlayer())
                            <div id="kisiitv-sod-twitch-player" class="absolute inset-0 w-full h-full"></div>
                            <script src="https://player.twitch.tv/js/embed/v1.js"></script>
                            <script>
                                (function () {
                                    if (window.location.hostname === '0.0.0.0' || window.location.hostname === '127.0.0.1') {
                                        window.location.replace(window.location.href.replace(window.location.hostname, 'localhost'));
                                        return;
                                    }

                                    var initTwitchPlayer = function () {
                                        if (typeof Twitch === 'undefined' || !Twitch.Player) {
                                            return;
                                        }
                                        var container = document.getElementById('kisiitv-sod-twitch-player');
                                        if (!container || container.dataset.initialized === 'true') {
                                            return;
                                        }
                                        container.dataset.initialized = 'true';

                                        var options = {{ Js::from($liveStream->getTwitchPlayerOptions(request()->getHost())) }};
                                        var currentHost = window.location.hostname || 'localhost';
                                        options.parent = [currentHost];
                                        options.width = '100%';
                                        options.height = '100%';
                                        options.autoplay = true;

                                        var player = new Twitch.Player('kisiitv-sod-twitch-player', options);
                                        player.addEventListener(Twitch.Player.READY, function () {
                                            try { player.play(); } catch (e) {}
                                        });
                                    };

                                    if (document.readyState === 'loading') {
                                        document.addEventListener('DOMContentLoaded', initTwitchPlayer);
                                    } else {
                                        initTwitchPlayer();
                                    }
                                })();
                            </script>
                        @else
                            <iframe
                                id="kisiitv-sod-iframe"
                                src="{{ $liveStream->getComputedEmbedUrl(request()->getHost()) }}"
                                title="{{ $liveStream->title }}"
                                class="absolute inset-0 w-full h-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen>
                            </iframe>
                            @if($liveStream->usesTwitchIframe())
                                <script>
                                    (function () {
                                        if (window.location.hostname === '0.0.0.0' || window.location.hostname === '127.0.0.1') {
                                            window.location.replace(window.location.href.replace(window.location.hostname, 'localhost'));
                                            return;
                                        }
                                        var iframe = document.getElementById('kisiitv-sod-iframe');
                                        if (iframe && iframe.src && iframe.src.indexOf('player.twitch.tv') !== -1) {
                                            var currentHost = window.location.hostname || 'localhost';
                                            var updatedSrc = iframe.src.replace(/parent=[^&]+/, 'parent=' + encodeURIComponent(currentHost));
                                            if (updatedSrc !== iframe.src) {
                                                iframe.src = updatedSrc;
                                            }
                                        }
                                    })();
                                </script>
                            @endif
                        @endif

                        @if($activePass)
                            <script>
                                (function () {
                                    var heartbeatUrl = @json(route('sod.heartbeat', $liveStream->slug));
                                    setInterval(function () {
                                        fetch(heartbeatUrl, {
                                            credentials: 'same-origin',
                                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                        }).then(function (res) {
                                            if (res.status === 403) {
                                                window.location.reload();
                                            }
                                        }).catch(function () {});
                                    }, 30000);
                                })();
                            </script>
                        @endif
                    @else
                        <!-- Locked SOD Paywall Poster (No Embed URL Exposed in DOM) -->
                        <div class="absolute inset-0 w-full h-full flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-950 via-[#0a1c3d] to-slate-950">
                            @if($liveStream->thumbnail)
                                <img src="{{ $liveStream->thumbnail }}" alt="{{ $liveStream->title }}"
                                    class="absolute inset-0 w-full h-full object-cover opacity-30 blur-[2px] scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/75 to-slate-950/60"></div>
                            @endif

                            <div class="relative z-10 max-w-2xl mx-auto px-6 py-8 text-center flex flex-col items-center">
                                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[10px] sm:text-xs font-black uppercase tracking-widest text-amber-300 mb-4 backdrop-blur-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Exclusive Pay-Per-View Live Stream</span>
                                </span>

                                <h1 class="text-xl sm:text-3xl md:text-4xl font-black font-headline text-white leading-tight tracking-tight mb-3">
                                    {{ $liveStream->title }}
                                </h1>

                                @if($liveStream->description)
                                    <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed mb-6 line-clamp-3">
                                        {{ $liveStream->description }}
                                    </p>
                                @endif

                                <!-- Primary Play / Stream CTA Button -->
                                <button type="button" @click="paywallOpen = true"
                                    class="group relative inline-flex items-center space-x-3 px-7 py-3.5 sm:px-9 sm:py-4 rounded-full bg-[#d9252a] hover:bg-red-600 text-white font-black uppercase tracking-wider text-xs sm:text-sm shadow-2xl shadow-red-600/40 ring-4 ring-red-500/20 hover:ring-red-500/40 transition-all duration-200 cursor-pointer">
                                    <span class="w-9 h-9 rounded-full bg-white text-[#d9252a] flex items-center justify-center shadow-inner group-hover:scale-105 transition">
                                        <svg class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                    <span class="text-left">
                                        <span class="block leading-none">Play / Stream Now</span>
                                        <span class="block text-[10px] font-bold text-red-100 mt-1 tracking-normal">
                                            {{ $price > 0 ? 'Unlock Pass for '.$currency.' '.$formattedPrice : 'Unlock Free Stream Pass' }}
                                        </span>
                                    </span>
                                </button>

                                <p class="mt-5 text-[11px] text-slate-400 flex items-center justify-center space-x-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Single-device protected pass — shared links will not play on other devices.</span>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stream Details Footer Card -->
            <div class="bg-slate-900/90 border border-slate-800 p-4 sm:p-5 rounded-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($liveStream->status === 'live')
                            <span class="bg-[#d9252a] text-white text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">LIVE NOW</span>
                        @elseif($liveStream->status === 'scheduled')
                            <span class="bg-amber-500 text-slate-950 text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">SCHEDULED</span>
                        @else
                            <span class="bg-slate-700 text-slate-200 text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">ON DEMAND</span>
                        @endif

                        <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">
                            SOD · {{ $price > 0 ? $currency.' '.$formattedPrice : 'FREE PASS' }}
                        </span>

                        <span class="text-xs text-slate-400">
                            {{ $liveStream->started_at ? 'Started '.$liveStream->started_at->diffForHumans() : 'Kisii TV Digital Broadcast' }}
                        </span>
                    </div>

                    <h2 class="text-base sm:text-lg font-black text-white">{{ $liveStream->title }}</h2>

                    @if($liveStream->description)
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            {{ $liveStream->description }}
                        </p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if(! $hasAccess)
                        <button type="button" @click="paywallOpen = true"
                            class="w-full md:w-auto px-5 py-2.5 rounded-lg bg-[#d9252a] hover:bg-red-600 text-white font-black uppercase tracking-wider text-xs transition flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span>Unlock &amp; Play Stream</span>
                        </button>
                    @else
                        <div class="text-xs text-slate-400 bg-slate-950/80 border border-slate-800 rounded-lg px-4 py-2.5">
                            <span class="block text-[10px] font-black uppercase tracking-wider text-emerald-400">Device-Locked Stream</span>
                            @if($activePass)
                                <span class="text-[11px] text-slate-300">Pass Ref: <strong class="font-mono text-white">{{ $activePass->order_reference }}</strong></span>
                            @else
                                <span class="text-[11px] text-slate-300">Newsroom Staff Preview</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- SOD PAYWALL MODAL (OPENS WHEN USER CLICKS PLAY)       -->
        <!-- ==================================================== -->
        @if(! $hasAccess)
            <div x-show="paywallOpen" x-cloak style="display: none;"
                @keydown.escape.window="paywallOpen = false"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">

                <div @click.outside="paywallOpen = false"
                    class="bg-slate-900 border-2 border-[#d9252a]/50 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">

                    <!-- Modal Header -->
                    <div class="bg-[#0a1c3d] px-6 py-4 border-b border-slate-800 flex items-start justify-between gap-4">
                        <div>
                            <span class="inline-block bg-[#d9252a]/20 text-red-300 border border-[#d9252a]/40 text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded mb-1">
                                Streaming On Demand Paywall
                            </span>
                            <h2 class="text-base sm:text-lg font-black font-headline text-white leading-snug">
                                {{ $liveStream->title }}
                            </h2>
                            <p class="text-xs text-slate-300 mt-1">
                                Stream Pass: <strong class="text-emerald-400">{{ $price > 0 ? $currency.' '.$formattedPrice : 'Free Access' }}</strong> · Unlocks immediately on this device
                            </p>
                        </div>
                        <button type="button" @click="paywallOpen = false"
                            class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Paywall Form -->
                    <form method="POST" action="{{ route('sod.purchase', $liveStream->slug) }}" class="p-6 space-y-4">
                        @csrf

                        @if($errors->any())
                            <div class="p-3 rounded-lg bg-red-950/90 border border-red-500/40 text-red-200 text-xs space-y-1">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Phone Number (M-Pesa / Mobile Money)
                            </label>
                            <input type="text" name="buyer_phone" value="{{ old('buyer_phone') }}"
                                placeholder="e.g. 0712345678 or 254712345678"
                                class="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Email Address
                            </label>
                            <input type="email" name="buyer_email" value="{{ old('buyer_email', auth()->user()?->email) }}"
                                placeholder="you@example.com"
                                class="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">Provide your phone number, email address, or both to receive your receipt.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Your Name <span class="text-slate-500 font-normal">(Optional)</span>
                            </label>
                            <input type="text" name="buyer_name" value="{{ old('buyer_name', auth()->user()?->name) }}"
                                placeholder="Full Name"
                                class="w-full px-3.5 py-2.5 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] outline-none">
                        </div>

                        @if($price > 0 && $activeGateways->isNotEmpty())
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                                    Payment Method
                                </label>
                                <div class="{{ $activeGateways->count() === 1 ? 'flex justify-center' : 'grid grid-cols-2 gap-2.5' }}">
                                    @foreach($activeGateways as $gw)
                                        <label @click="gateway = '{{ $gw->code }}'"
                                            :class="gateway === '{{ $gw->code }}' ? 'border-[#d9252a] bg-[#d9252a]/10 ring-1 ring-[#d9252a]' : 'border-slate-700 bg-slate-950 hover:border-slate-500'"
                                            class="relative flex items-center justify-center p-3 rounded-lg border cursor-pointer transition {{ $activeGateways->count() === 1 ? 'w-full max-w-xs' : '' }}">
                                            <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="gateway" class="sr-only">
                                            <div class="text-center">
                                                @if($gw->logoUrl())
                                                    <img src="{{ $gw->logoUrl() }}" alt="{{ $gw->name }}" class="h-7 mx-auto object-contain mb-1">
                                                @endif
                                                <span class="block text-xs font-bold text-white">{{ $gw->provider_label ?: $gw->name }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="p-3 rounded-lg bg-slate-950/90 border border-slate-800 text-[11px] text-slate-400 leading-relaxed">
                            <strong class="text-slate-200">Anti-Sharing Protection:</strong> Once payment is confirmed, this stream unlocks strictly on this browser/device. If you share the link, other viewers will see the paywall.
                        </div>

                        <button type="submit"
                            class="w-full py-3.5 px-6 rounded-xl bg-[#d9252a] hover:bg-red-600 text-white font-black uppercase tracking-wider text-xs sm:text-sm shadow-lg shadow-red-600/25 transition flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span>{{ $price > 0 ? 'Pay '.$currency.' '.$formattedPrice.' & Unlock Stream' : 'Unlock Free Stream Now' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
