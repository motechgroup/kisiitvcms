@extends('layouts.admin')

@section('title', 'Site Settings & Theme Customization')
@section('page_title', 'Site Settings, Branding & Theme Customization')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ request('tab', 'branding') }}',
    primaryColor: '{{ $settings['primary_color'] }}',
    accentColor: '{{ $settings['accent_color'] }}',
    headerBgColor: '{{ $settings['header_bg_color'] }}',
    tickerBgColor: '{{ $settings['ticker_bg_color'] }}'
}">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#0a1c3d] via-[#1b52bc] to-[#0a1c3d] text-white p-6 rounded-lg shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="bg-amber-400 text-slate-950 text-[10px] font-black uppercase px-2 py-0.5 rounded tracking-wider">Super Admin</span>
                <span class="text-xs text-blue-200">System Sovereignty</span>
            </div>
            <h2 class="text-xl font-extrabold font-headline mt-1">Platform Branding &amp; Configuration Engine</h2>
            <p class="text-xs text-blue-100 mt-0.5">Control site logos, brand color palette, footer copyright, newsroom tip lines, and live ticker toggles.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="px-4 py-2 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded transition shrink-0 shadow">
            Preview Public Site &rarr;
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap items-center gap-1 bg-white p-1.5 rounded-lg border border-slate-200 shadow-sm text-xs font-bold">
        <button type="button" @click="activeTab = 'branding'" :class="activeTab === 'branding' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition">
            1. Branding &amp; Logo
        </button>
        <button type="button" @click="activeTab = 'theme'" :class="activeTab === 'theme' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition">
            2. Theme &amp; Colors
        </button>
        <button type="button" @click="activeTab = 'footer'" :class="activeTab === 'footer' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition">
            3. Footer &amp; Contact Desk
        </button>
        <button type="button" @click="activeTab = 'social'" :class="activeTab === 'social' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition">
            4. Social Media Networks
        </button>
        <button type="button" @click="activeTab = 'editorial'" :class="activeTab === 'editorial' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition cursor-pointer">
            5. Editorial &amp; Tickers
        </button>
        <button type="button" @click="activeTab = 'monetization'" :class="activeTab === 'monetization' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition cursor-pointer flex items-center space-x-1.5">
            <span>6. AdSense &amp; Google Analytics</span>
            <span class="px-1.5 py-0.5 rounded bg-amber-400 text-slate-950 text-[9px] font-black uppercase">Ads &amp; GA4</span>
        </button>
        <button type="button" @click="activeTab = 'sms'" :class="activeTab === 'sms' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition cursor-pointer flex items-center space-x-1.5">
            <span>7. SMS &amp; Ticket Email</span>
            <span class="px-1.5 py-0.5 rounded bg-emerald-500 text-slate-950 text-[9px] font-black uppercase">Ticketing</span>
        </button>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 lg:p-8 space-y-6">
        @csrf
        <input type="hidden" name="active_tab" :value="activeTab">

        <!-- ============================================== -->
        <!-- TAB 1: BRANDING & IDENTITY                     -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'branding'" class="space-y-6">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Site Identity &amp; Logos</h3>
                <p class="text-xs text-slate-500 mt-0.5">Define media network name, official tagline, and upload custom logos.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Site Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                    <span class="text-[10px] text-slate-400 mt-1 block">Displayed in header, page titles, and meta tags.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Slogan / Editorial Tagline
                    </label>
                    <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                    <span class="text-[10px] text-slate-400 mt-1 block">e.g. "Amandegere Y'Omogusii"</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Meta Description &amp; Summary
                </label>
                <textarea name="site_description" rows="3"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">{{ old('site_description', $settings['site_description']) }}</textarea>
            </div>

            <!-- Logo Configuration -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-4">
                <div class="font-bold text-xs uppercase tracking-wider text-slate-900">Official Brand Logo</div>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="w-48 h-20 bg-slate-900 rounded p-2 flex items-center justify-center shrink-0 border border-slate-700">
                        <img src="{{ $settings['site_logo'] ?: asset('images/kisii-tv-logo.png') }}" alt="Current Logo" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="space-y-3 flex-grow">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-700 mb-1">Upload New Logo Image</label>
                            <input type="file" name="site_logo_file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[#1b52bc] file:text-white hover:file:bg-blue-800">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-700 mb-1">Or Direct Logo Image URL</label>
                            <input type="url" name="site_logo_url" value="{{ old('site_logo_url', $settings['site_logo']) }}" placeholder="https://..." class="w-full text-xs p-2 border border-slate-300 rounded">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Favicon Configuration -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-4">
                <div class="font-bold text-xs uppercase tracking-wider text-slate-900">Browser Favicon</div>

                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 bg-slate-900 rounded p-2 flex items-center justify-center shrink-0 border border-slate-700">
                        <img src="{{ $settings['site_favicon'] ?: asset('images/kisii-tv-logo.png') }}" alt="Favicon" class="w-8 h-8 object-contain">
                    </div>
                    <div class="space-y-2 flex-grow">
                        <input type="file" name="site_favicon_file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-white">
                        <input type="url" name="site_favicon_url" value="{{ old('site_favicon_url', $settings['site_favicon']) }}" placeholder="Or Favicon URL..." class="w-full text-xs p-1.5 border border-slate-300 rounded">
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 2: THEME & COLOR CUSTOMIZER                -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'theme'" class="space-y-6">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Theme Styling &amp; Dynamic Color Palette</h3>
                <p class="text-xs text-slate-500 mt-0.5">Customize the live broadcast appearance. Colors update immediately on the public website.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- 1. Primary Royal Blue -->
                <div class="p-4 border border-slate-200 rounded-lg bg-slate-50 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">Primary Brand Color</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" x-model="primaryColor" name="primary_color" class="w-10 h-10 border-0 rounded cursor-pointer">
                        <input type="text" x-model="primaryColor" name="primary_color" class="w-full font-mono text-xs p-2 border border-slate-300 rounded uppercase font-bold">
                    </div>
                    <span class="text-[10px] text-slate-400 block">Navigation bars, category links, button accents.</span>
                </div>

                <!-- 2. Accent Crimson Red -->
                <div class="p-4 border border-slate-200 rounded-lg bg-slate-50 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">Accent Crimson Red</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" x-model="accentColor" name="accent_color" class="w-10 h-10 border-0 rounded cursor-pointer">
                        <input type="text" x-model="accentColor" name="accent_color" class="w-full font-mono text-xs p-2 border border-slate-300 rounded uppercase font-bold">
                    </div>
                    <span class="text-[10px] text-slate-400 block">Breaking labels, live badges, primary action triggers.</span>
                </div>

                <!-- 3. Header Midnight Navy -->
                <div class="p-4 border border-slate-200 rounded-lg bg-slate-50 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">Header Midnight Navy</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" x-model="headerBgColor" name="header_bg_color" class="w-10 h-10 border-0 rounded cursor-pointer">
                        <input type="text" x-model="headerBgColor" name="header_bg_color" class="w-full font-mono text-xs p-2 border border-slate-300 rounded uppercase font-bold">
                    </div>
                    <span class="text-[10px] text-slate-400 block">Top newsroom header &amp; broadcast footer background.</span>
                </div>

                <!-- 4. Breaking Ticker Color -->
                <div class="p-4 border border-slate-200 rounded-lg bg-slate-50 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">Breaking News Ticker</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" x-model="tickerBgColor" name="ticker_bg_color" class="w-10 h-10 border-0 rounded cursor-pointer">
                        <input type="text" x-model="tickerBgColor" name="ticker_bg_color" class="w-full font-mono text-xs p-2 border border-slate-300 rounded uppercase font-bold">
                    </div>
                    <span class="text-[10px] text-slate-400 block">Flashing breaking ticker banner background.</span>
                </div>
            </div>

            <!-- Live Theme Swatch Preview -->
            <div class="mt-4 p-5 rounded-lg border border-slate-300 space-y-3" :style="'background-color: ' + headerBgColor">
                <span class="text-[10px] font-black uppercase tracking-widest text-white/70 block">Live Header &amp; Ticker Swatch Simulation</span>
                <div class="flex items-center justify-between p-3 rounded" :style="'background-color: ' + primaryColor">
                    <span class="text-xs font-bold text-white uppercase tracking-wider">Navigation Bar Preview</span>
                    <span class="px-2 py-0.5 text-[10px] font-black uppercase text-white rounded" :style="'background-color: ' + accentColor">Active Tab</span>
                </div>
                <div class="p-2.5 rounded text-xs text-white font-bold flex items-center space-x-2" :style="'background-color: ' + tickerBgColor">
                    <span class="bg-black text-white px-2 py-0.5 text-[9px] font-black uppercase">Breaking Flash</span>
                    <span>Live ticker simulation text preview...</span>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 3: FOOTER & CONTACT DESK                   -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'footer'" class="space-y-6">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Site Footer &amp; Newsroom Contact Details</h3>
                <p class="text-xs text-slate-500 mt-0.5">Manage footer mission statement, copyright text, confidential tip lines, and newsletter copy.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Footer About Us / Mission Statement
                </label>
                <textarea name="footer_about" rows="3"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">{{ old('footer_about', $settings['footer_about']) }}</textarea>
                <span class="text-[10px] text-slate-400 mt-1 block">Displayed prominently in the public site footer column.</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Contact Email <span class="text-red-600">*</span>
                    </label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" required
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Newsroom Hotline Phone
                    </label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Studio / Bureau Physical Address
                    </label>
                    <input type="text" name="newsroom_address" value="{{ old('newsroom_address', $settings['newsroom_address']) }}"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Newsletter Box Headline
                    </label>
                    <input type="text" name="newsletter_title" value="{{ old('newsletter_title', $settings['newsletter_title']) }}"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Newsletter Description Blurb
                    </label>
                    <input type="text" name="newsletter_description" value="{{ old('newsletter_description', $settings['newsletter_description']) }}"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Footer Copyright Notice
                </label>
                <input type="text" name="footer_copyright" value="{{ old('footer_copyright', $settings['footer_copyright']) }}"
                    class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc] focus:ring-1 focus:ring-[#1b52bc]">
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 4: SOCIAL MEDIA NETWORKS                   -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'social'" class="space-y-6">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Official Social Media Profiles</h3>
                <p class="text-xs text-slate-500 mt-0.5">Links to official broadcast channels shown across the header and footer.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">X / Twitter</label>
                    <input type="url" name="social_x" value="{{ old('social_x', $settings['social_x']) }}" placeholder="https://x.com/kisiitv"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Facebook Page</label>
                    <input type="url" name="social_facebook" value="{{ old('social_facebook', $settings['social_facebook']) }}" placeholder="https://facebook.com/kisiitv"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">YouTube Broadcast Channel</label>
                    <input type="url" name="social_youtube" value="{{ old('social_youtube', $settings['social_youtube']) }}" placeholder="https://youtube.com/@kisiitv"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">TikTok Channel</label>
                    <input type="url" name="social_tiktok" value="{{ old('social_tiktok', $settings['social_tiktok']) }}" placeholder="https://tiktok.com/@kisiitv"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Instagram Profile</label>
                    <input type="url" name="social_instagram" value="{{ old('social_instagram', $settings['social_instagram']) }}" placeholder="https://instagram.com/kisiitv"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">WhatsApp Broadcast Channel</label>
                    <input type="url" name="social_whatsapp" value="{{ old('social_whatsapp', $settings['social_whatsapp']) }}" placeholder="https://whatsapp.com/channel/..."
                        class="w-full text-xs p-2.5 border border-slate-300 rounded focus:border-[#1b52bc]">
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 5: EDITORIAL TOGGLES                       -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'editorial'" class="space-y-6">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Editorial Global Switches</h3>
                <p class="text-xs text-slate-500 mt-0.5">Toggle live features across the platform.</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded cursor-pointer hover:bg-slate-100">
                    <input type="checkbox" name="breaking_ticker_enabled" value="1" {{ $settings['breaking_ticker_enabled'] ? 'checked' : '' }}
                        class="mt-1 rounded border-slate-300 text-[#d9252a] focus:ring-[#d9252a]">
                    <div class="ml-3">
                        <span class="block text-xs font-bold text-slate-900">Enable Breaking News Ticker</span>
                        <span class="text-[11px] text-slate-500">Show the flashing live news ticker banner below the navigation header.</span>
                    </div>
                </label>

                <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded cursor-pointer hover:bg-slate-100">
                    <input type="checkbox" name="enable_comments" value="1" {{ $settings['enable_comments'] ? 'checked' : '' }}
                        class="mt-1 rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                    <div class="ml-3">
                        <span class="block text-xs font-bold text-slate-900">Enable Reader Comments System</span>
                        <span class="text-[11px] text-slate-500">Allow authenticated and guest readers to submit comments under articles.</span>
                    </div>
                </label>

                <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded cursor-pointer hover:bg-slate-100">
                    <input type="checkbox" name="require_comment_moderation" value="1" {{ $settings['require_comment_moderation'] ? 'checked' : '' }}
                        class="mt-1 rounded border-slate-300 text-[#1b52bc] focus:ring-[#1b52bc]">
                    <div class="ml-3">
                        <span class="block text-xs font-bold text-slate-900">Require Pre-Publication Comment Moderation</span>
                        <span class="text-[11px] text-slate-500">Comments must be approved by an editor before becoming visible publicly.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 6: GOOGLE ADSENSE & GOOGLE ANALYTICS (GA4) -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'monetization'" x-cloak class="space-y-8">
            <div class="border-b border-slate-200 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">Google AdSense &amp; Google Analytics Configuration</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Configure Google AdSense publisher monetization, ad slot units, <code>ads.txt</code>, and Google Analytics (GA4) visitor telemetry.</p>
                </div>
                <a href="{{ route('seo.ads-txt') }}" target="_blank" class="text-xs font-bold text-[#1b52bc] hover:underline shrink-0">
                    Preview /ads.txt &rarr;
                </a>
            </div>

            <!-- 1. GOOGLE ANALYTICS (GA4) & SEARCH CONSOLE -->
            <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-[#1b52bc] block">Audience &amp; Traffic Telemetry</span>
                        <h4 class="text-sm font-extrabold text-slate-900 font-headline">Google Analytics 4 (GA4) &amp; Search Console</h4>
                    </div>
                    <label class="inline-flex items-center space-x-2 px-3 py-1.5 rounded bg-white border border-slate-300 cursor-pointer">
                        <input type="checkbox" name="google_analytics_enabled" value="1" {{ $settings['google_analytics_enabled'] ? 'checked' : '' }}
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">Enable Google Analytics</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Google Analytics Measurement ID (GA4)
                        </label>
                        <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $settings['google_analytics_id']) }}"
                            placeholder="e.g. G-XXXXXXXXXX"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                        <span class="text-[10px] text-slate-400 mt-1 block">Automatically injects the official Google tag (<code>gtag.js</code>) across all public pages.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Google Search Console Verification Code (Optional)
                        </label>
                        <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $settings['google_site_verification']) }}"
                            placeholder="Paste content value from <meta name='google-site-verification' content='...'>"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                        <span class="text-[10px] text-slate-400 mt-1 block">Renders <code>&lt;meta name="google-site-verification" content="..."&gt;</code> in the site header.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Custom Analytics / Google Tag Manager Script (Optional)
                    </label>
                    <textarea name="google_analytics_custom_script" rows="3"
                        placeholder="Optional: Paste custom Google Tag Manager or third-party analytics <script> snippet..."
                        class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('google_analytics_custom_script', $settings['google_analytics_custom_script']) }}</textarea>
                </div>
            </div>

            <!-- 2. GOOGLE ADSENSE MONETIZATION -->
            <div class="p-5 bg-amber-50/40 border border-amber-200 rounded-xl space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-amber-200">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-amber-700 block">Ad Network Monetization</span>
                        <h4 class="text-sm font-extrabold text-slate-900 font-headline">Google AdSense Auto Ads &amp; Display Ad Units</h4>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center space-x-2 px-3 py-1.5 rounded bg-white border border-slate-300 cursor-pointer">
                            <input type="checkbox" name="adsense_auto_ads" value="1" {{ $settings['adsense_auto_ads'] ? 'checked' : '' }}
                                class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-bold text-slate-800">Enable Auto Ads Script</span>
                        </label>
                        <label class="inline-flex items-center space-x-2 px-3 py-1.5 rounded bg-white border border-emerald-400 cursor-pointer">
                            <input type="checkbox" name="adsense_enabled" value="1" {{ $settings['adsense_enabled'] ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-900">Enable Google AdSense</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            AdSense Publisher Client ID
                        </label>
                        <input type="text" name="adsense_client_id" value="{{ old('adsense_client_id', $settings['adsense_client_id']) }}"
                            placeholder="e.g. ca-pub-1234567890123456"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                        <span class="text-[10px] text-slate-500 mt-1 block">Automatically loads <code>adsbygoogle.js?client=ca-pub-...</code> and populates <code>/ads.txt</code>.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Custom AdSense <code>&lt;head&gt;</code> Verification Script (Optional)
                        </label>
                        <textarea name="adsense_head_script" rows="2"
                            placeholder="Optional: Paste custom <script async src='https://pagead2.googlesyndication.com/...'></script> if needed"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('adsense_head_script', $settings['adsense_head_script']) }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-amber-200/60">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Top Header Leaderboard Slot (728x90)
                        </label>
                        <textarea name="adsense_header_code" rows="4"
                            placeholder="Paste AdSense <ins class='adsbygoogle'>... code OR numeric data-ad-slot ID"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('adsense_header_code', $settings['adsense_header_code']) }}</textarea>
                        <span class="text-[10px] text-slate-500 mt-1 block">Replaces the top 728x90 header banner when filled.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Sidebar MREC Ad Slot (300x250)
                        </label>
                        <textarea name="adsense_sidebar_code" rows="4"
                            placeholder="Paste AdSense <ins class='adsbygoogle'>... code OR numeric data-ad-slot ID"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('adsense_sidebar_code', $settings['adsense_sidebar_code']) }}</textarea>
                        <span class="text-[10px] text-slate-500 mt-1 block">Displayed in the sidebar on homepage, articles, and categories.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            In-Article &amp; Mid-Feed Ad Slot
                        </label>
                        <textarea name="adsense_in_article_code" rows="4"
                            placeholder="Paste AdSense <ins class='adsbygoogle'>... code OR numeric data-ad-slot ID"
                            class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('adsense_in_article_code', $settings['adsense_in_article_code']) }}</textarea>
                        <span class="text-[10px] text-slate-500 mt-1 block">Displayed inside article stories and homepage mid-feed billboard.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Authorized Digital Sellers (<code>/ads.txt</code>) Content (Optional)
                    </label>
                    <textarea name="adsense_ads_txt" rows="2"
                        placeholder="google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0 (Auto-generated from Publisher Client ID if left blank)"
                        class="w-full text-xs p-2.5 font-mono border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('adsense_ads_txt', $settings['adsense_ads_txt']) }}</textarea>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 7: SMS GATEWAY (TEXTSMS KENYA)             -->
        <!-- ============================================== -->
        <div x-show="activeTab === 'sms'" x-cloak style="display: none;" class="space-y-6" x-data="{ showSmsKey: false }">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 font-headline">SMS Gateway Configuration (Event Tickets &amp; Alerts)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Configure TextSMS (Kenya) to automatically deliver digital event ticket links via SMS immediately after purchase.</p>
            </div>

            <div class="max-w-md">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Active SMS Provider</label>
                <select name="sms_driver" class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc] font-semibold">
                    <option value="textsms" {{ old('sms_driver', $settings['sms_driver']) === 'textsms' ? 'selected' : '' }}>TextSMS (Kenya) — sms.textsms.co.ke</option>
                    <option value="africastalking" {{ old('sms_driver', $settings['sms_driver']) === 'africastalking' ? 'selected' : '' }}>Africa's Talking</option>
                    <option value="webhook" {{ old('sms_driver', $settings['sms_driver']) === 'webhook' ? 'selected' : '' }}>Custom HTTP Webhook</option>
                    <option value="log" {{ old('sms_driver', $settings['sms_driver']) === 'log' ? 'selected' : '' }}>Log Only (Testing / No Real SMS)</option>
                </select>
            </div>

            <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">TextSMS (Kenya) Credentials</h4>
                        <p class="text-[11px] text-slate-500">Bulk SMS API endpoint: <code class="font-mono text-slate-700">https://sms.textsms.co.ke/api/services/sendsms/</code></p>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase">Kenya Ready</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">API Key</label>
                        <div class="relative">
                            <input :type="showSmsKey ? 'text' : 'password'" name="sms_textsms_api_key"
                                value="{{ old('sms_textsms_api_key', $settings['sms_textsms_api_key']) }}"
                                class="w-full text-xs font-mono p-2.5 pr-10 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                            <button type="button" @click="showSmsKey = !showSmsKey"
                                class="absolute inset-y-0 right-0 px-2.5 text-slate-400 hover:text-slate-700 cursor-pointer" title="Toggle visibility">
                                <svg x-show="!showSmsKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showSmsKey" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Partner ID</label>
                        <input type="text" name="sms_textsms_partner_id"
                            value="{{ old('sms_textsms_partner_id', $settings['sms_textsms_partner_id']) }}"
                            placeholder="e.g. 14712"
                            class="w-full text-xs font-mono p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Shortcode / Sender ID</label>
                        <input type="text" name="sms_textsms_shortcode"
                            value="{{ old('sms_textsms_shortcode', $settings['sms_textsms_shortcode']) }}"
                            placeholder="e.g. TextSMS"
                            class="w-full text-xs font-mono p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">API Host</label>
                        <input type="text" name="sms_textsms_host"
                            value="{{ old('sms_textsms_host', $settings['sms_textsms_host']) }}"
                            placeholder="sms.textsms.co.ke"
                            class="w-full text-xs font-mono p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>
                </div>
            </div>

            <!-- Ticketing-Only Email Template Customizer -->
            <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Ticket Confirmation Email Template (<code>ticket@kisiitv.co.ke</code>)</h4>
                        <p class="text-[11px] text-slate-500">Customize the branded HTML ticket email sent to buyers. Supported placeholders: <code>{event_title}</code>, <code>{order_reference}</code>, <code>{buyer_name}</code>.</p>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold uppercase">Ticketing Only</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Email Subject Line</label>
                        <input type="text" name="ticket_email_subject"
                            value="{{ old('ticket_email_subject', $settings['ticket_email_subject']) }}"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Header Subtitle</label>
                        <input type="text" name="ticket_email_heading"
                            value="{{ old('ticket_email_heading', $settings['ticket_email_heading']) }}"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Intro / Confirmation Message</label>
                        <textarea name="ticket_email_intro" rows="2"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('ticket_email_intro', $settings['ticket_email_intro']) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Gate Check-In Instructions</label>
                        <textarea name="ticket_email_instructions" rows="2"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">{{ old('ticket_email_instructions', $settings['ticket_email_instructions']) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Footer Support Note</label>
                        <input type="text" name="ticket_email_footer"
                            value="{{ old('ticket_email_footer', $settings['ticket_email_footer']) }}"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded bg-white focus:border-[#1b52bc]">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="pt-6 border-t border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-500">Settings take effect immediately upon saving.</span>
            <button type="submit" class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded shadow transition cursor-pointer">
                Save All Site Settings &rarr;
            </button>
        </div>

    </form>

    <!-- Standalone Live SMS Gateway Tester (Shown on SMS Tab) -->
    <div x-show="activeTab === 'sms'" x-cloak style="display: none;"
        class="bg-slate-900 text-white border border-slate-800 rounded-xl shadow-md p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Live Diagnostic Tool</span>
                <h3 class="text-sm font-extrabold uppercase tracking-wider font-headline mt-0.5">Test SMS Gateway Delivery</h3>
                <p class="text-xs text-slate-400 mt-0.5">Send a real test SMS right now to verify your TextSMS credentials and delivery speed. Save any changes above before testing.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.test-sms') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-xs">
            @csrf
            <div class="md:col-span-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-300 mb-1">Recipient Phone Number *</label>
                <input type="tel" name="test_phone" value="{{ old('test_phone') }}" required
                    placeholder="e.g. 0712345678 or 254712345678"
                    class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-400 rounded-lg px-3 py-2.5 font-mono text-white focus:outline-none">
            </div>
            <div class="md:col-span-5">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-300 mb-1">Test Message (Optional)</label>
                <input type="text" name="test_message"
                    value="{{ old('test_message', 'Kisii TV SMS Gateway Test: Your TextSMS integration is live and working!') }}"
                    class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-400 rounded-lg px-3 py-2.5 text-white focus:outline-none">
            </div>
            <div class="md:col-span-3">
                <button type="submit"
                    class="w-full py-2.5 px-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black uppercase tracking-wider rounded-lg shadow transition cursor-pointer">
                    Send Test SMS &rarr;
                </button>
            </div>
        </form>

        <!-- Test SMTP Email -->
        <div class="pt-5 border-t border-slate-800 space-y-3">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-400">Live Email Diagnostic</span>
                <h4 class="text-sm font-extrabold uppercase tracking-wider font-headline mt-0.5">Test SMTP Email Delivery</h4>
                <p class="text-xs text-slate-400 mt-0.5">
                    Current Mailer: <code class="text-blue-300">{{ config('mail.default') }}</code> &bull;
                    From: <code class="text-blue-300">{{ config('mail.from.address') }}</code>
                </p>
            </div>
            <form method="POST" action="{{ route('admin.settings.test-email') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-xs">
                @csrf
                <div class="md:col-span-9">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-300 mb-1">Recipient Email Address *</label>
                    <input type="email" name="test_email" value="{{ old('test_email', auth()->user()?->email) }}" required
                        placeholder="you@example.com"
                        class="w-full bg-slate-950 border border-slate-700 focus:border-blue-400 rounded-lg px-3 py-2.5 text-white focus:outline-none">
                </div>
                <div class="md:col-span-3">
                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#1b52bc] hover:bg-blue-600 text-white font-black uppercase tracking-wider rounded-lg shadow transition cursor-pointer">
                        Send Test Email &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
