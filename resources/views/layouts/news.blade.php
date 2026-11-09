<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Breaking News, Latest Headlines & In-Depth Analysis' }} - {{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}</title>
    <meta name="description" content="{{ $metaDescription ?? \App\Models\SiteSetting::get('site_description', 'Kisii TV (Amandegere Y\'Omogusii) delivers fearless investigations, breaking news, politics, devolution, business, culture, and national Kenyan affairs.') }}">
    <meta name="keywords" content="{{ $metaKeywords ?? \App\Models\SiteSetting::get('site_keywords', 'Kisii TV, Amandegere Y’omogusii, Kisii News, Kenya Breaking News, Kevin Ombwori, Gusii Politics, Nyamira News, Devolution Kenya, Gusii Culture') }}">
    <meta name="author" content="{{ $authorName ?? 'Kisii TV Newsroom' }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">

    <!-- Local & Regional SEO Geo Tags -->
    <meta name="geo.region" content="KE-16">
    <meta name="geo.placename" content="Kisii, Kenya">
    <meta name="geo.position" content="-0.6817;34.7667">
    <meta name="ICBM" content="-0.6817, 34.7667">

    <!-- Open Graph Protocol (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:site_name" content="{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }} - {{ \App\Models\SiteSetting::get('site_tagline', 'Amandegere Y\'Omogusii') }}">
    <meta property="og:locale" content="en_KE">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($title ?? 'Breaking News, Latest Headlines & In-Depth Analysis') . ' - ' . \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($metaDescription ?? \App\Models\SiteSetting::get('site_description')) }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? (\App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png')) }}">
    <meta property="og:image:alt" content="{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }} Logo">
    @yield('extra_og_tags')

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@kisiitv">
    <meta name="twitter:creator" content="@kisiitv">
    <meta name="twitter:title" content="{{ $ogTitle ?? ($title ?? 'Breaking News, Latest Headlines & In-Depth Analysis') . ' - ' . \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}">
    <meta name="twitter:description" content="{{ $ogDescription ?? ($metaDescription ?? \App\Models\SiteSetting::get('site_description')) }}">
    <meta name="twitter:image" content="{{ $ogImage ?? (\App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png')) }}">

    <!-- News RSS Syndication Auto-Discovery -->
    <link rel="alternate" type="application/rss+xml" title="{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }} RSS Feed" href="{{ route('seo.rss') }}">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('seo.sitemap') }}">

    <!-- Google Fonts: Playfair Display for Editorial Headlines & Instrument Sans for Sharp Copy -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- JSON-LD Structured Data: NewsMediaOrganization & WebSite -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@graph": [
            {
                "@type": "NewsMediaOrganization",
                "@id": "{{ url('/#organization') }}",
                "name": "{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}",
                "alternateName": ["Kisii TV News", "Kisii TV Amandegere Y'Omogusii", "Kisii Television"],
                "url": "{{ url('/') }}",
                "logo": {
                    "@type": "ImageObject",
                    "@id": "{{ url('/#logo') }}",
                    "url": "{{ \App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png') }}",
                    "caption": "{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}"
                },
                "image": "{{ \App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png') }}",
                "slogan": "{{ \App\Models\SiteSetting::get('site_tagline', 'Amandegere Y\'Omogusii') }}",
                "description": "{{ \App\Models\SiteSetting::get('site_description') }}",
                "email": "{{ \App\Models\SiteSetting::get('contact_email', 'kisiitelevision@gmail.com') }}",
                "telephone": "{{ \App\Models\SiteSetting::get('contact_phone', '+254726547363') }}",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "{{ \App\Models\SiteSetting::get('newsroom_address', 'Sansora Complex, Kisii Town & Nairobi Media Center') }}",
                    "addressLocality": "Kisii",
                    "addressRegion": "Kisii County",
                    "addressCountry": "KE"
                },
                "sameAs": [
                    "{{ \App\Models\SiteSetting::get('social_youtube', 'https://www.youtube.com/@kisiitv') }}",
                    "{{ \App\Models\SiteSetting::get('social_facebook', 'https://www.facebook.com/KisiitvAmandegere') }}",
                    "{{ \App\Models\SiteSetting::get('social_tiktok', 'https://www.tiktok.com/@kevinombworisnr') }}",
                    "{{ \App\Models\SiteSetting::get('social_x', 'https://x.com/kisiitv') }}",
                    "{{ \App\Models\SiteSetting::get('social_instagram', 'https://www.instagram.com/kisiitv') }}",
                    "{{ \App\Models\SiteSetting::get('social_whatsapp', 'https://wa.me/254726547363') }}"
                ]
            },
            {
                "@type": "WebSite",
                "@id": "{{ url('/#website') }}",
                "url": "{{ url('/') }}",
                "name": "{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}",
                "publisher": { "@id": "{{ url('/#organization') }}" },
                "inLanguage": "en-KE",
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": "{{ url('/#search') }}?q={search_term_string}",
                    "query-input": "required name=search_term_string"
                }
            }
        ]
    }
    </script>
    @stack('schema')

    <style>
        :root {
            --brand-primary: {{ \App\Models\SiteSetting::get('primary_color', '#1b52bc') }};
            --brand-accent: {{ \App\Models\SiteSetting::get('accent_color', '#d9252a') }};
            --brand-header-bg: {{ \App\Models\SiteSetting::get('header_bg_color', '#0a1c3d') }};
            --brand-ticker-bg: {{ \App\Models\SiteSetting::get('ticker_bg_color', '#d9252a') }};
        }
        .font-headline {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .font-sans-body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        /* Editorial border styling */
        .editorial-rule {
            border-bottom: 3px solid #0b162c;
        }
        .editorial-subrule {
            border-bottom: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="min-h-full font-sans-body antialiased text-slate-900 bg-white flex flex-col justify-between selection:bg-[#d9252a] selection:text-white" x-data="{ mobileMenuOpen: false, searchOpen: false }">

    <!-- 1. TOP UTILITY / DESK BAR -->
    <div class="bg-[#0b162c] text-white text-xs border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5 flex items-center justify-between">
            <!-- Left: Current Date -->
            <div class="flex items-center text-slate-300">
                <span class="font-medium tracking-wide">
                    {{ now()->timezone('Africa/Nairobi')->format('l, F j, Y') }}
                </span>
            </div>

            <!-- Center: Official Kisii TV Social Handles & Live Hotline -->
            <div class="hidden md:flex items-center space-x-3 text-slate-300">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Follow Us:</span>
                <a href="{{ \App\Models\SiteSetting::get('social_youtube', 'https://www.youtube.com/@kisiitv') }}" target="_blank" rel="noopener" class="text-slate-300 hover:text-red-500 transition flex items-center space-x-1" title="YouTube @kisiitv">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    <span class="text-[11px] font-semibold">YouTube</span>
                </a>
                <a href="{{ \App\Models\SiteSetting::get('social_facebook', 'https://www.facebook.com/KisiitvAmandegere') }}" target="_blank" rel="noopener" class="text-slate-300 hover:text-blue-400 transition flex items-center space-x-1" title="Facebook @KisiitvAmandegere">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span class="text-[11px] font-semibold">Facebook</span>
                </a>
                <a href="{{ \App\Models\SiteSetting::get('social_tiktok', 'https://www.tiktok.com/@kevinombworisnr') }}" target="_blank" rel="noopener" class="text-slate-300 hover:text-pink-400 transition flex items-center space-x-1" title="TikTok @kevinombworisnr">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.66c0 1.95-.56 3.94-1.74 5.48-1.47 1.97-3.83 3.12-6.28 3.15-2.45.03-4.87-1.03-6.42-2.92-1.66-2.01-2.22-4.75-1.64-7.29.62-2.73 2.64-4.99 5.31-5.83.69-.22 1.41-.33 2.14-.34v4.06c-.84.05-1.69.36-2.31.96-.86.83-1.17 2.13-.81 3.27.35 1.11 1.37 1.93 2.53 2.05 1.17.12 2.37-.48 2.95-1.52.33-.58.46-1.25.46-1.91V.02z"/></svg>
                    <span class="text-[11px] font-semibold">TikTok</span>
                </a>
                <a href="{{ \App\Models\SiteSetting::get('social_x', 'https://x.com/kisiitv') }}" target="_blank" rel="noopener" class="text-slate-300 hover:text-white transition flex items-center space-x-1" title="X / Twitter @kisiitv">
                    <span class="font-bold text-xs">𝕏</span>
                    <span class="text-[11px] font-semibold">@kisiitv</span>
                </a>
                <a href="{{ \App\Models\SiteSetting::get('social_whatsapp', 'https://wa.me/254726547363') }}" target="_blank" rel="noopener" class="text-emerald-400 hover:text-emerald-300 transition flex items-center space-x-1" title="WhatsApp Hotline +254 726 547 363">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <span class="text-[11px] font-bold">Hotline</span>
                </a>
            </div>

            <!-- Right: Live Stream & Access -->
            <div class="flex items-center space-x-4">
                <a href="#live-coverage" class="flex items-center space-x-1.5 text-red-400 hover:text-white transition font-bold text-xs uppercase tracking-wider">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    <span>Watch Live</span>
                </a>

                <span class="text-slate-600">|</span>

                @auth
                    <!-- Authenticated User Profile Pill & Dropdown -->
                    <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
                        <button type="button" @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center space-x-1.5 py-1 px-2.5 rounded-full bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white transition focus:outline-none border border-slate-700 cursor-pointer shadow-sm">
                            <!-- User Profile Avatar Icon -->
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}"
                                class="w-5 h-5 rounded-full object-cover border border-slate-500 shrink-0">
                            <!-- Username -->
                            <span class="text-xs font-bold font-mono text-blue-300">
                                {{ '@' . auth()->user()->username }}
                            </span>
                            <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <div x-show="userMenuOpen"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            style="display: none;"
                            class="absolute right-0 mt-2 w-64 bg-slate-900 border border-slate-700 rounded-lg shadow-2xl py-2 z-50 text-xs">

                            <!-- User Info Header -->
                            <div class="px-4 py-2.5 border-b border-slate-800 flex items-center space-x-3">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                                    class="w-10 h-10 rounded-full object-cover border-2 border-slate-600 shrink-0">
                                <div class="min-w-0 flex-grow">
                                    <div class="font-bold text-white truncate">{{ auth()->user()->name }}</div>
                                    <div class="font-mono text-[11px] text-blue-400 font-semibold truncate">{{ '@' . auth()->user()->username }}</div>
                                    <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()->job_title ?? 'Kisii TV Staff' }}</div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-1">
                                <a href="{{ route('admin.profile.edit') }}"
                                    class="flex items-center space-x-2.5 px-4 py-2 text-slate-200 hover:bg-[#1b52bc] hover:text-white transition">
                                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="font-semibold">Profile Settings &amp; Info</span>
                                </a>

                                <a href="{{ route('admin.dashboard') }}"
                                    class="flex items-center space-x-2.5 px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    <span>Newsroom Editorial Desk</span>
                                </a>

                                @if(auth()->user()->isSuperAdmin())
                                    <a href="{{ route('admin.settings.index') }}"
                                        class="flex items-center space-x-2.5 px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-white transition">
                                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Platform &amp; Theme Settings</span>
                                    </a>
                                @endif
                            </div>

                            <!-- Logout -->
                            <div class="pt-1 border-t border-slate-800">
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center space-x-2.5 px-4 py-2 text-slate-400 hover:text-red-400 hover:bg-slate-800 transition text-left cursor-pointer">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-slate-300 hover:text-white font-medium">Staff Login</a>
                        <span class="text-slate-600">|</span>
                        <a href="{{ route('register') }}" class="text-slate-300 hover:text-white font-medium">Join</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <!-- 2. MAIN BRAND HEADER & LEADERBOARD AD -->
    <header class="border-b border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4 flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Brand Logo from Dynamic Settings -->
            <div class="flex items-center justify-between w-full md:w-auto">
                <a href="{{ route('home') }}" class="group flex items-center space-x-3">
                    <img src="{{ \App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png') }}" alt="{{ \App\Models\SiteSetting::get('site_name', 'Kisiitv News') }}" class="h-14 sm:h-16 w-auto object-contain drop-shadow">
                    <div class="flex flex-col">
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl sm:text-3xl font-black font-headline tracking-tight text-[#0a1c3d]">KISII<span class="text-[#d9252a]">TV</span></span>
                            <span class="text-[10px] sm:text-xs font-black tracking-widest text-[#1b52bc] uppercase hidden sm:inline">News Network</span>
                        </div>
                        <span class="text-[11px] sm:text-xs font-semibold text-[#1b52bc] tracking-wide">{{ \App\Models\SiteSetting::get('site_tagline', "Amandegere Y'Omogusii") }}</span>
                    </div>
                </a>

                <!-- Mobile Hamburger Toggle -->
                <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-800 p-2 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Top Header Ad Placement (728x90) -->
            <div class="hidden lg:block w-[728px] h-[90px] bg-slate-50 border border-slate-200 relative overflow-hidden group">
                @if(isset($headerAd) && $headerAd)
                    <a href="{{ $headerAd->target_url }}" target="_blank" rel="noopener" class="block w-full h-full">
                        @if($headerAd->creative_type === 'image')
                            <img src="{{ $headerAd->image_path }}" alt="{{ $headerAd->title }}" class="w-full h-full object-cover">
                        @else
                            {!! $headerAd->html_code !!}
                        @endif
                        <span class="absolute bottom-0 right-0 bg-slate-900/80 text-[9px] text-slate-300 px-1 font-mono uppercase">Advertisement</span>
                    </a>
                @else
                    <div class="w-full h-full flex items-center justify-between px-6 bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-red-300">Advertise With Kisii TV</div>
                            <div class="text-sm font-bold">Reach 2.5 Million Viewers & Readers Across Kenya</div>
                        </div>
                        <a href="{{ route('login') }}" class="px-4 py-1.5 bg-[#d9252a] hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider transition shadow">
                            Commercial Desk
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. PRIMARY HORIZONTAL NAVIGATION (DYNAMIC COLORS) -->
        <nav style="background-color: var(--brand-primary); border-bottom-color: var(--brand-accent);" class="text-white border-b-4 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <!-- Category Links -->
                    @php
                        $mainNavLimit = 8;
                        $primaryNavCategories = $navCategories->take($mainNavLimit);
                        $moreNavCategories = $navCategories->skip($mainNavLimit);
                    @endphp

                    <div class="hidden md:flex items-center space-x-0.5 overflow-visible py-0">
                        <a href="{{ route('home') }}" class="px-2.5 lg:px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-[#d9252a] transition whitespace-nowrap {{ request()->is('/') ? 'bg-[#d9252a]' : '' }}">
                            Home
                        </a>

                        @foreach($primaryNavCategories as $cat)
                            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="{{ route('categories.show', $cat->slug) }}" class="px-2 lg:px-2.5 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-[#0f3d99] transition flex items-center space-x-1 whitespace-nowrap">
                                    <span>{{ $cat->name }}</span>
                                    @if($cat->children->isNotEmpty())
                                        <svg class="w-3 h-3 text-blue-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                </a>

                                <!-- Subcategory Dropdown -->
                                @if($cat->children->isNotEmpty())
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute left-0 mt-0 w-52 bg-white text-slate-900 shadow-xl border-t-2 border-[#d9252a] py-2 z-50">
                                        @foreach($cat->children as $sub)
                                            <a href="{{ route('categories.show', $sub->slug) }}" class="block px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-[#d9252a] transition">
                                                {{ $sub->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($moreNavCategories->isNotEmpty())
                            <!-- More Sections Dropdown -->
                            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <button type="button" class="px-2.5 lg:px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-blue-100 hover:text-white hover:bg-[#0f3d99] transition flex items-center space-x-1 whitespace-nowrap">
                                    <span>More</span>
                                    <svg class="w-3 h-3 text-blue-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 mt-0 w-64 bg-white text-slate-900 shadow-2xl border-t-2 border-[#d9252a] p-3 z-50 divide-y divide-slate-100">
                                    <div class="text-[10px] font-black uppercase tracking-widest text-[#d9252a] pb-1.5 px-2">
                                        More News Sections
                                    </div>
                                    <div class="py-1 space-y-0.5">
                                        @foreach($moreNavCategories as $cat)
                                            <a href="{{ route('categories.show', $cat->slug) }}" class="flex items-center justify-between px-2 py-1.5 text-xs font-bold text-slate-800 hover:bg-slate-50 hover:text-[#1b52bc] transition">
                                                <span>{{ $cat->name }}</span>
                                                @if($cat->children->isNotEmpty())
                                                    <span class="text-[10px] text-slate-400 font-normal">({{ $cat->children->count() }})</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Search Trigger Icon -->
                    <div class="flex items-center py-2">
                        <button type="button" @click="searchOpen = !searchOpen" class="text-slate-300 hover:text-white p-1 focus:outline-none flex items-center space-x-1 text-xs uppercase font-bold tracking-wider">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span class="hidden sm:inline">Search</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dropdown Search Drawer -->
        <div x-show="searchOpen" x-transition class="bg-slate-900 border-b border-slate-700 py-4 px-4">
            <div class="max-w-3xl mx-auto">
                <form action="#" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Search breaking news, politics, business, county developments..."
                        class="w-full bg-slate-800 text-white placeholder-slate-400 px-4 py-3 text-sm border border-slate-600 focus:outline-none focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a]">
                    <button type="submit" class="absolute right-2 top-2 bg-[#d9252a] text-white px-4 py-1.5 font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-[#0b162c] text-white border-t border-slate-700 px-4 py-4 space-y-2">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-sm font-bold uppercase tracking-wider text-white bg-[#d9252a]">
                Home
            </a>
            @foreach($navCategories as $cat)
                <div class="border-b border-slate-800 pb-1">
                    <a href="{{ route('categories.show', $cat->slug) }}" class="block px-3 py-2 text-sm font-bold uppercase tracking-wider text-slate-200">
                        {{ $cat->name }}
                    </a>
                    @if($cat->children->isNotEmpty())
                        <div class="pl-6 space-y-1 pb-1">
                            @foreach($cat->children as $sub)
                                <a href="{{ route('categories.show', $sub->slug) }}" class="block py-1 text-xs text-slate-400 hover:text-white">
                                    {{ $sub->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Mobile Social Channels -->
            <div class="pt-4 mt-2 border-t border-slate-800">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Follow Us</div>
                <div class="flex items-center space-x-3 text-slate-300">
                    <a href="{{ \App\Models\SiteSetting::get('social_youtube', 'https://www.youtube.com/@kisiitv') }}" target="_blank" rel="noopener" class="w-8 h-8 rounded bg-slate-800 hover:bg-red-600 hover:text-white flex items-center justify-center transition" title="YouTube">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <a href="{{ \App\Models\SiteSetting::get('social_facebook', 'https://www.facebook.com/KisiitvAmandegere') }}" target="_blank" rel="noopener" class="w-8 h-8 rounded bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition" title="Facebook">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="{{ \App\Models\SiteSetting::get('social_tiktok', 'https://www.tiktok.com/@kevinombworisnr') }}" target="_blank" rel="noopener" class="w-8 h-8 rounded bg-slate-800 hover:bg-pink-600 hover:text-white flex items-center justify-center transition" title="TikTok">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.66c0 1.95-.56 3.94-1.74 5.48-1.47 1.97-3.83 3.12-6.28 3.15-2.45.03-4.87-1.03-6.42-2.92-1.66-2.01-2.22-4.75-1.64-7.29.62-2.73 2.64-4.99 5.31-5.83.69-.22 1.41-.33 2.14-.34v4.06c-.84.05-1.69.36-2.31.96-.86.83-1.17 2.13-.81 3.27.35 1.11 1.37 1.93 2.53 2.05 1.17.12 2.37-.48 2.95-1.52.33-.58.46-1.25.46-1.91V.02z"/></svg>
                    </a>
                    <a href="{{ \App\Models\SiteSetting::get('social_x', 'https://x.com/kisiitv') }}" target="_blank" rel="noopener" class="w-8 h-8 rounded bg-slate-800 hover:bg-slate-700 hover:text-white flex items-center justify-center transition font-bold text-xs" title="X / Twitter">
                        𝕏
                    </a>
                    <a href="{{ \App\Models\SiteSetting::get('social_whatsapp', 'https://wa.me/254726547363') }}" target="_blank" rel="noopener" class="w-8 h-8 rounded bg-slate-800 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition" title="WhatsApp Hotline">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- 4. BREAKING NEWS TICKER -->
    @if(\App\Models\SiteSetting::get('breaking_ticker_enabled', true) && isset($breakingNews) && $breakingNews->isNotEmpty())
        <section style="background-color: var(--brand-ticker-bg);" class="text-white shadow-sm overflow-hidden border-b-2 border-red-900" x-data="{
            currentIndex: 0,
            items: {{ Js::from($breakingNews->pluck('alert_text')) }},
            links: {{ Js::from($breakingNews->map(fn($item) => $item->article ? route('articles.show', $item->article->slug) : ($item->url ?? route('home')))) }},
            timer: null,
            init() {
                if (this.items.length > 1) {
                    this.timer = setInterval(() => {
                        this.currentIndex = (this.currentIndex + 1) % this.items.length;
                    }, 6000);
                }
            }
        }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center">
                <!-- Red Pill Label -->
                <div class="flex items-center space-x-2 bg-slate-950 text-white uppercase text-[11px] font-black px-3 py-1 tracking-wider shrink-0 mr-4 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-600"></span>
                    </span>
                    <span>Breaking News</span>
                </div>

                <!-- Ticker headline slider -->
                <div class="overflow-hidden flex-grow relative h-6">
                    <template x-for="(text, index) in items" :key="index">
                        <div x-show="currentIndex === index"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform translate-y-3"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-3"
                            class="absolute inset-0 flex items-center">
                            <a :href="links[index]" class="text-white hover:underline font-bold text-xs sm:text-sm tracking-wide truncate block" x-text="text"></a>
                        </div>
                    </template>
                </div>

                <!-- Controls -->
                <div class="hidden sm:flex items-center space-x-2 ml-4 shrink-0 text-white/80 text-xs">
                    <button type="button" @click="currentIndex = (currentIndex - 1 + items.length) % items.length" class="p-1 hover:text-white">
                        &#9664;
                    </button>
                    <span x-text="(currentIndex + 1) + '/' + items.length" class="font-mono text-[10px]"></span>
                    <button type="button" @click="currentIndex = (currentIndex + 1) % items.length" class="p-1 hover:text-white">
                        &#9654;
                    </button>
                </div>
            </div>
        </section>
    @endif

    <!-- 5. MAIN EDITORIAL CONTENT -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- 6. EDITORIAL FOOTER -->
    <footer class="bg-[#0a1c3d] text-white border-t-4 border-[#d9252a] mt-16 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Footer Top: Organization brand & Newsletter -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-10 border-b border-slate-800">
                <div class="lg:col-span-5 space-y-4">
                    <div class="flex items-center space-x-3">
                        <img src="{{ \App\Models\SiteSetting::get('site_logo') ?: asset('images/kisii-tv-logo.png') }}" alt="{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}" class="h-14 sm:h-16 w-auto object-contain">
                        <div class="flex flex-col">
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-black font-headline tracking-tight text-white">{{ \App\Models\SiteSetting::get('site_name', 'Kisii TV') }}</span>
                            </div>
                            <span class="text-xs font-semibold text-blue-300">{{ \App\Models\SiteSetting::get('site_tagline', "Amandegere Y'Omogusii") }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed pr-6">
                        {{ \App\Models\SiteSetting::get('footer_about', 'Kisii TV (Amandegere Y\'Omogusii) is an independent Kenyan broadcast and digital news organization committed to fearless investigative reporting, authoritative political analysis, comprehensive county governance coverage, cultural storytelling, and live national affairs.') }}
                    </p>
                    <div class="text-xs font-semibold text-blue-300">
                        {{ \App\Models\SiteSetting::get('site_tagline', "Amandegere Y'Omogusii") }} &bull; {{ \App\Models\SiteSetting::get('newsroom_address', 'Sansora Complex, Kisii Town & Nairobi Media Center, Kenya') }}
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 pt-1">
                        <span>Desk: <a href="mailto:{{ \App\Models\SiteSetting::get('contact_email', 'kisiitelevision@gmail.com') }}" class="text-white hover:underline">{{ \App\Models\SiteSetting::get('contact_email', 'kisiitelevision@gmail.com') }}</a></span>
                        <span>•</span>
                        <span>Hotline: <a href="tel:{{ str_replace(' ', '', \App\Models\SiteSetting::get('contact_phone', '+254726547363')) }}" class="text-white hover:underline">{{ \App\Models\SiteSetting::get('contact_phone', '+254 726 547 363') }}</a></span>
                    </div>

                    <!-- Connect With Kisii TV Social Pill Badges -->
                    <div class="pt-2">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Connect Across Platforms:</div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <a href="{{ \App\Models\SiteSetting::get('social_youtube', 'https://www.youtube.com/@kisiitv') }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white transition flex items-center space-x-1.5 font-semibold text-[11px]">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                <span>YouTube</span>
                            </a>
                            <a href="{{ \App\Models\SiteSetting::get('social_facebook', 'https://www.facebook.com/KisiitvAmandegere') }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white transition flex items-center space-x-1.5 font-semibold text-[11px]">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                <span>Facebook</span>
                            </a>
                            <a href="{{ \App\Models\SiteSetting::get('social_tiktok', 'https://www.tiktok.com/@kevinombworisnr') }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 rounded bg-pink-600/20 text-pink-400 hover:bg-pink-600 hover:text-white transition flex items-center space-x-1.5 font-semibold text-[11px]">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.66c0 1.95-.56 3.94-1.74 5.48-1.47 1.97-3.83 3.12-6.28 3.15-2.45.03-4.87-1.03-6.42-2.92-1.66-2.01-2.22-4.75-1.64-7.29.62-2.73 2.64-4.99 5.31-5.83.69-.22 1.41-.33 2.14-.34v4.06c-.84.05-1.69.36-2.31.96-.86.83-1.17 2.13-.81 3.27.35 1.11 1.37 1.93 2.53 2.05 1.17.12 2.37-.48 2.95-1.52.33-.58.46-1.25.46-1.91V.02z"/></svg>
                                <span>TikTok</span>
                            </a>
                            <a href="{{ \App\Models\SiteSetting::get('social_x', 'https://x.com/kisiitv') }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 rounded bg-slate-800 text-slate-300 hover:bg-white hover:text-slate-950 transition flex items-center space-x-1 font-bold text-[11px]">
                                <span>𝕏</span>
                                <span>@kisiitv</span>
                            </a>
                            <a href="{{ \App\Models\SiteSetting::get('social_whatsapp', 'https://wa.me/254726547363') }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 rounded bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white transition flex items-center space-x-1.5 font-semibold text-[11px]">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Newsletter Subscription Box -->
                <div class="lg:col-span-7 bg-slate-900 p-6 border border-slate-800">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="bg-red-600 text-white text-[10px] font-black uppercase px-1.5 py-0.5 tracking-wider">Morning Briefing</span>
                        <h3 class="text-base font-bold text-white uppercase tracking-tight">Subscribe to the Daily Wire</h3>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">
                        Receive curations of the top investigative stories, market analysis, and breaking Kenya headlines delivered straight to your inbox every morning at 06:00 EAT.
                    </p>
                    <form action="#" method="POST" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <input type="email" placeholder="Enter your email address..." required
                            class="bg-slate-800 text-white placeholder-slate-500 text-xs px-4 py-2.5 flex-grow border border-slate-700 focus:outline-none focus:border-[#d9252a]">
                        <button type="submit" class="bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider px-6 py-2.5 transition shrink-0 cursor-pointer">
                            Subscribe Now
                        </button>
                    </form>
                </div>
            </div>

            <!-- Category Sitemap Links -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6 py-8 border-b border-slate-800 text-xs">
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">News</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('categories.show', 'kenya') }}" class="hover:text-white transition">Kenya Headlines</a></li>
                        <li><a href="{{ route('categories.show', 'politics') }}" class="hover:text-white transition">Politics & Law</a></li>
                        <li><a href="{{ route('categories.show', 'counties') }}" class="hover:text-white transition">Counties & Devolution</a></li>
                        <li><a href="{{ route('categories.show', 'crime') }}" class="hover:text-white transition">Crime & Courts</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">Markets & Economy</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('categories.show', 'business') }}" class="hover:text-white transition">Financial Markets</a></li>
                        <li><a href="{{ route('categories.show', 'business') }}" class="hover:text-white transition">Banking & Fintech</a></li>
                        <li><a href="{{ route('categories.show', 'business') }}" class="hover:text-white transition">Agribusiness</a></li>
                        <li><a href="{{ route('categories.show', 'technology') }}" class="hover:text-white transition">Silicon Savannah</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">World</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('categories.show', 'world') }}" class="hover:text-white transition">East Africa Community</a></li>
                        <li><a href="{{ route('categories.show', 'world') }}" class="hover:text-white transition">Pan-African Affairs</a></li>
                        <li><a href="{{ route('categories.show', 'world') }}" class="hover:text-white transition">Global Geopolitics</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">Culture & Sports</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('categories.show', 'sports') }}" class="hover:text-white transition">Athletics & Marathons</a></li>
                        <li><a href="{{ route('categories.show', 'sports') }}" class="hover:text-white transition">Football & Rugby</a></li>
                        <li><a href="{{ route('categories.show', 'entertainment') }}" class="hover:text-white transition">Music & Arts</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">Opinion</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('categories.show', 'opinion') }}" class="hover:text-white transition">Editorials</a></li>
                        <li><a href="{{ route('categories.show', 'opinion') }}" class="hover:text-white transition">Guest Columnists</a></li>
                        <li><a href="{{ route('categories.show', 'opinion') }}" class="hover:text-white transition">Letters to Editor</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-200 uppercase tracking-wider mb-3 text-[11px] text-[#d9252a]">Newsroom & Legal</h4>
                    <ul class="space-y-1.5 text-slate-400">
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Editorial Desk Portal</a></li>
                        <li><a href="#" class="hover:text-white transition">Editorial Policy</a></li>
                        <li><a href="#" class="hover:text-white transition">Corrections Policy</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacy & Terms</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom: Legal, Copyright & SEO Feeds -->
            <div class="pt-6 flex flex-col md:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>&copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('footer_copyright', 'Kisii TV Media Network (Amandegere Y’Omogusii). All rights reserved.') }}</p>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <a href="{{ route('seo.sitemap') }}" class="hover:text-slate-300 transition flex items-center space-x-1">
                        <span>Sitemap (XML)</span>
                    </a>
                    <a href="{{ route('seo.rss') }}" class="text-amber-400 hover:text-amber-300 transition flex items-center space-x-1">
                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44m0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93V10.1z"/></svg>
                        <span class="font-semibold">RSS Feed</span>
                    </a>
                    <a href="#" class="hover:text-slate-300 transition">About Kisii TV</a>
                    <a href="#" class="hover:text-slate-300 transition">Code of Ethics</a>
                    <a href="#" class="hover:text-slate-300 transition">Advertise With Us</a>
                    <a href="#" class="hover:text-slate-300 transition">Contact Bureau</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
