@extends('layouts.admin')

@section('title', 'Site Settings & Theme Customization')
@section('page_title', 'Site Settings, Branding & Theme Customization')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'branding',
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
        <button type="button" @click="activeTab = 'editorial'" :class="activeTab === 'editorial' ? 'bg-[#1b52bc] text-white shadow' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded transition">
            5. Editorial &amp; Tickers
        </button>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 lg:p-8 space-y-6">
        @csrf

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

        <!-- Submit Bar -->
        <div class="pt-6 border-t border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-500">Settings take effect immediately upon saving.</span>
            <button type="submit" class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white text-xs font-black uppercase tracking-wider rounded shadow transition">
                Save All Site Settings &rarr;
            </button>
        </div>

    </form>

</div>
@endsection
