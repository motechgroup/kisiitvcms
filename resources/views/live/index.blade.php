@extends('layouts.news')

@section('content')
<div class="bg-slate-950 text-white border-b-4 border-[#d9252a]"
    x-data="{
        donateModalOpen: {{ $errors->any() ? 'true' : 'false' }},
        amount: '{{ old('amount', 500) }}',
        gateway: '{{ old('gateway_code', $activeGateways->first()?->code ?? 'mpesa') }}'
    }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-950/90 border-l-4 border-emerald-400 text-emerald-100 p-4 rounded shadow-lg flex items-center justify-between text-xs sm:text-sm">
                <div class="flex items-center space-x-2.5">
                    <span class="bg-emerald-500 text-slate-950 font-black px-2 py-0.5 rounded text-[10px] uppercase">Confirmed</span>
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-950/90 border-l-4 border-red-500 text-red-100 p-4 rounded shadow-lg flex items-center space-x-2.5 text-xs sm:text-sm">
                <span class="bg-red-600 text-white font-black px-2 py-0.5 rounded text-[10px] uppercase">Alert</span>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Studio Top Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-800">
            <div class="flex items-center space-x-3">
                <span class="relative flex h-3.5 w-3.5 shrink-0">
                    @if($activeStream && $activeStream->status === 'live')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#d9252a]"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                    @endif
                </span>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-black uppercase tracking-widest text-[#d9252a]">Kisii TV Digital Broadcast Studio</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black font-headline tracking-tight text-white mt-0.5">
                        {{ $activeStream ? $activeStream->title : 'Kisii TV Live Broadcast Desk' }}
                    </h1>
                </div>
            </div>

            @if($activeStream)
                @php
                    $shareUrl = route('live.show', $activeStream->slug);
                    $shareTitle = $activeStream->title;
                @endphp
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <!-- Social Share Icons (WhatsApp, Facebook, X, Copy Link) -->
                    <div class="flex items-center space-x-1.5" x-data="{ copied: false }">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Share:</span>

                        <!-- WhatsApp -->
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($shareTitle . ' - ' . $shareUrl) }}"
                            target="_blank" rel="noopener" title="Share on WhatsApp"
                            class="w-8 h-8 rounded bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white border border-emerald-500/30 flex items-center justify-center transition">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </a>

                        <!-- Facebook -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                            target="_blank" rel="noopener" title="Share on Facebook"
                            class="w-8 h-8 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white border border-blue-500/30 flex items-center justify-center transition">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>

                        <!-- X / Twitter -->
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareTitle) }}&url={{ urlencode($shareUrl) }}&via=kisiitv"
                            target="_blank" rel="noopener" title="Share on X"
                            class="w-8 h-8 rounded bg-slate-800 text-slate-200 hover:bg-white hover:text-slate-950 border border-slate-700 flex items-center justify-center transition">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>

                        <!-- Copy Link -->
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            title="Copy Stream Link"
                            class="px-2.5 h-8 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 flex items-center space-x-1 text-[11px] font-semibold transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>

                    @if($activeStream->areDonationsActive())
                        <button type="button" @click="donateModalOpen = true"
                            class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase tracking-wider text-xs transition shadow-lg flex items-center space-x-1.5 cursor-pointer">
                            <span>♥</span>
                            <span>Donate / Support Stream</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>

        @if($activeStream)
            <!-- Full-Width Responsive Video Player & Stream Details -->
            <div class="space-y-5 sm:space-y-6">
                <div class="w-full bg-black border border-slate-800 sm:border-2 shadow-2xl overflow-hidden rounded-md sm:rounded-lg">
                    <div class="relative w-full aspect-video max-h-[80vh] mx-auto" style="aspect-ratio: 16 / 9;">
                        @if($activeStream->isTwitch())
                            <!-- Twitch Interactive JavaScript Video Player (No Manual Iframe) -->
                            <div id="kisiitv-twitch-player" class="absolute inset-0 w-full h-full"></div>
                            <script src="https://player.twitch.tv/js/embed/v1.js"></script>
                            <script>
                                (function () {
                                    // Twitch rejects 0.0.0.0 and 127.0.0.1 as parent domains; redirect local IP binds to localhost
                                    if (window.location.hostname === '0.0.0.0' || window.location.hostname === '127.0.0.1') {
                                        window.location.replace(window.location.href.replace(window.location.hostname, 'localhost'));
                                        return;
                                    }

                                    var initTwitchPlayer = function () {
                                        if (typeof Twitch === 'undefined' || !Twitch.Player) {
                                            return;
                                        }
                                        var container = document.getElementById('kisiitv-twitch-player');
                                        if (!container || container.dataset.initialized === 'true') {
                                            return;
                                        }
                                        container.dataset.initialized = 'true';

                                        var options = {{ Js::from($activeStream->getTwitchPlayerOptions(request()->getHost())) }};
                                        var currentHost = window.location.hostname || 'localhost';
                                        options.parent = [currentHost];
                                        options.width = '100%';
                                        options.height = '100%';
                                        options.autoplay = true;

                                        var player = new Twitch.Player('kisiitv-twitch-player', options);
                                        player.addEventListener(Twitch.Player.READY, function () {
                                            try {
                                                player.play();
                                            } catch (e) {}
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
                                src="{{ $activeStream->getComputedEmbedUrl(request()->getHost()) }}"
                                title="{{ $activeStream->title }}"
                                class="absolute inset-0 w-full h-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen>
                            </iframe>
                        @endif
                    </div>
                </div>

                <!-- Stream Details & Description Card -->
                <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            @if($activeStream->status === 'live')
                                <span class="bg-[#d9252a] text-white text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">LIVE NOW</span>
                            @elseif($activeStream->status === 'scheduled')
                                <span class="bg-amber-500 text-slate-950 text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">SCHEDULED</span>
                            @else
                                <span class="bg-slate-700 text-slate-200 text-[10px] font-black uppercase px-2.5 py-0.5 tracking-wider rounded">RECORDED BROADCAST</span>
                            @endif
                            <span class="text-xs text-slate-400">
                                {{ $activeStream->started_at ? 'Started '.$activeStream->started_at->diffForHumans() : 'Kisii TV Newsroom' }}
                            </span>
                        </div>

                        @if($activeStream->description)
                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                                {{ $activeStream->description }}
                            </p>
                        @endif
                    </div>

                    @if($activeStream->areDonationsActive())
                        <div class="shrink-0">
                            <button type="button" @click="donateModalOpen = true"
                                class="px-4 py-2.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase tracking-wider text-xs transition shadow cursor-pointer whitespace-nowrap">
                                ♥ Support Live Stream
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Recent Supporters Feed -->
                @if($activeStream->areDonationsActive() && $recentDonations->isNotEmpty())
                    <div class="bg-slate-900/80 border border-slate-800 p-5 rounded-lg">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 pb-2.5 mb-3 border-b border-slate-800 flex items-center justify-between">
                            <span>Recent Live Stream Supporters</span>
                            <span class="text-[10px] text-slate-400 font-normal">Thank you for backing independent journalism</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($recentDonations as $supporter)
                                <div class="p-3 rounded bg-slate-950/70 border border-slate-800/80 flex items-start justify-between gap-2 text-xs">
                                    <div>
                                        <div class="font-bold text-white">{{ $supporter->displayName() }}</div>
                                        @if($supporter->message)
                                            <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-2">"{{ $supporter->message }}"</p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-black text-emerald-400 block">{{ $supporter->currency }} {{ number_format((float) $supporter->amount, 0) }}</span>
                                        <span class="text-[9px] uppercase font-bold text-slate-500">{{ strtoupper($supporter->gateway_code) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- ==================================================== -->
            <!-- DONATION / PAYMENT MODAL (OPENS WHEN USER CLICKS)     -->
            <!-- ==================================================== -->
            @if($activeStream->areDonationsActive())
                <div x-show="donateModalOpen" x-cloak style="display: none;"
                    @keydown.escape.window="donateModalOpen = false"
                    class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">

                    <div @click.outside="donateModalOpen = false"
                        class="bg-slate-900 border-2 border-emerald-500/40 rounded-xl w-full max-w-lg shadow-2xl overflow-hidden">

                        <!-- Modal Header -->
                        <div class="bg-[#0a1c3d] px-6 py-4 border-b border-slate-800 flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-block bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded mb-1">
                                    Live Stream Support
                                </span>
                                <h2 class="text-base sm:text-lg font-black font-headline text-white leading-snug">
                                    {{ $activeStream->donation_call_to_action ?: 'Support Kisii TV Live Coverage' }}
                                </h2>
                            </div>
                            <button type="button" @click="donateModalOpen = false"
                                class="text-slate-400 hover:text-white text-xl font-bold leading-none p-1 cursor-pointer">
                                &times;
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4">
                            @if($errors->any())
                                <div class="bg-red-950/90 border-l-4 border-red-500 text-red-100 p-3 rounded text-xs space-y-1">
                                    <div class="font-bold uppercase tracking-wider text-red-300">Please check your donation form:</div>
                                    <ul class="list-disc list-inside text-red-200">
                                        @foreach($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($activeGateways->isNotEmpty())
                                <form method="POST" action="{{ route('donations.store') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="live_stream_id" value="{{ $activeStream->id }}">

                                    <!-- Quick Amount Buttons -->
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-2">
                                            1. Select or Enter Amount
                                        </label>
                                        <div class="grid grid-cols-4 gap-2 mb-2.5">
                                            @foreach([100, 500, 1000, 5000] as $preset)
                                                <button type="button"
                                                    @click="amount = '{{ $preset }}'"
                                                    :class="amount == '{{ $preset }}' ? 'bg-emerald-600 text-white border-emerald-400' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'"
                                                    class="py-2 px-2 text-xs font-black rounded border transition cursor-pointer">
                                                    {{ number_format($preset) }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="relative">
                                            <input type="number" name="amount" x-model="amount" min="10" step="any" required
                                                placeholder="Enter custom amount..."
                                                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded px-3.5 py-2.5 text-sm font-bold text-white focus:outline-none">
                                        </div>
                                    </div>

                                    <!-- Payment Gateway Selection (M-Pesa, Paystack, PayPal, Flutterwave) -->
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-2">
                                            2. Choose Payment Method
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($activeGateways as $gw)
                                                <label @click="gateway = '{{ $gw->code }}'"
                                                    :class="gateway === '{{ $gw->code }}' ? 'border-emerald-400 bg-emerald-950/40 text-white' : 'border-slate-700 bg-slate-950/60 text-slate-300 hover:border-slate-600'"
                                                    class="border-2 rounded p-2.5 cursor-pointer transition flex flex-col justify-between">
                                                    <div class="flex items-center justify-between">
                                                        <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="gateway" class="sr-only">
                                                        <span class="text-xs font-black uppercase tracking-wide">{{ $gw->code === 'mpesa' ? 'M-Pesa' : ucfirst($gw->code) }}</span>
                                                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-emerald-300">{{ $gw->currency }}</span>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 mt-1 line-clamp-1">{{ $gw->provider_label ?? $gw->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Donor Information -->
                                    <div class="space-y-3 pt-1">
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">Your Name *</label>
                                            <input type="text" name="donor_name" value="{{ old('donor_name', auth()->user()?->name) }}" required
                                                placeholder="e.g. Eng. Kemunto Nyabuto"
                                                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded px-3 py-2 text-xs text-white focus:outline-none">
                                        </div>

                                        <div x-show="gateway === 'mpesa'" x-cloak>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-emerald-400 mb-1">M-Pesa Phone Number *</label>
                                            <input type="text" name="donor_phone" value="{{ old('donor_phone') }}"
                                                placeholder="e.g. 0712345678 or 254712345678"
                                                class="w-full bg-slate-950 border border-emerald-500/60 focus:border-emerald-400 rounded px-3 py-2 text-xs text-white focus:outline-none">
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">Email Address (Optional)</label>
                                            <input type="email" name="donor_email" value="{{ old('donor_email', auth()->user()?->email) }}"
                                                placeholder="you@example.com"
                                                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded px-3 py-2 text-xs text-white focus:outline-none">
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">Support Comment (Optional)</label>
                                            <textarea name="message" rows="2" placeholder="Leave a message for the live desk..."
                                                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded px-3 py-2 text-xs text-white focus:outline-none">{{ old('message') }}</textarea>
                                        </div>

                                        <label class="flex items-center space-x-2 text-xs text-slate-400 cursor-pointer">
                                            <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                                                class="rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0">
                                            <span>Make my contribution anonymous on the public feed</span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-800">
                                        <button type="button" @click="donateModalOpen = false"
                                            class="px-4 py-2.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="py-2.5 px-5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider rounded shadow-lg transition cursor-pointer flex items-center space-x-2">
                                            <span>Proceed to Donate</span>
                                            <span>&rarr;</span>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="p-4 rounded bg-slate-950 border border-slate-800 text-xs text-slate-400 text-center">
                                    Payment gateways are currently being configured by the administrator.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty state when no stream is configured yet -->
            <div class="bg-slate-900 border border-slate-800 rounded-lg p-10 text-center space-y-3">
                <span class="inline-block px-3 py-1 rounded bg-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider">Off Air</span>
                <h2 class="text-xl font-bold font-headline text-white">No Active Live Broadcast Right Now</h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto">
                    Our broadcast engineers are preparing the next live stream. Check back shortly or explore breaking stories on our homepage.
                </p>
                <div class="pt-2">
                    <a href="{{ route('home') }}" class="inline-block px-5 py-2.5 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                        Return to Front Page
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
