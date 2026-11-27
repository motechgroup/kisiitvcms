<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display the site settings and theme customization panel.
     */
    public function index(): View
    {
        $settings = [
            // Branding & Identity
            'site_name' => SiteSetting::get('site_name', 'Kisiitv News'),
            'site_tagline' => SiteSetting::get('site_tagline', "Amandegere Y'Omogusii"),
            'site_description' => SiteSetting::get('site_description', 'Premier digital newsroom delivering breaking news, fearless investigations, in-depth politics, business, county developments, and world reporting.'),
            'site_logo' => SiteSetting::get('site_logo', ''),
            'site_favicon' => SiteSetting::get('site_favicon', ''),

            // Theme & Colors
            'primary_color' => SiteSetting::get('primary_color', '#1b52bc'),
            'accent_color' => SiteSetting::get('accent_color', '#d9252a'),
            'header_bg_color' => SiteSetting::get('header_bg_color', '#0a1c3d'),
            'ticker_bg_color' => SiteSetting::get('ticker_bg_color', '#d9252a'),

            // Contact & Newsroom Desk
            'contact_email' => SiteSetting::get('contact_email', 'newsroom@kisiitv.com'),
            'contact_phone' => SiteSetting::get('contact_phone', '+254 700 000 000'),
            'newsroom_address' => SiteSetting::get('newsroom_address', 'Media Center, Nairobi & Kisii, Kenya'),

            // Footer Configuration
            'footer_about' => SiteSetting::get('footer_about', 'Kisiitv News Media Organization is an independent digital news publishing and broadcast network delivering objective, fast, and comprehensive regional and national reporting.'),
            'footer_copyright' => SiteSetting::get('footer_copyright', 'Kisiitv News Media Organization. Registered digital broadcaster and publisher. All rights reserved.'),
            'newsletter_title' => SiteSetting::get('newsletter_title', 'The Daily Briefing - Morning Dispatch'),
            'newsletter_description' => SiteSetting::get('newsletter_description', 'Receive hand-curated top political scoops, investigative files, county updates and markets directly to your inbox every dawn.'),

            // Social Channels
            'social_x' => SiteSetting::get('social_x', 'https://x.com/kisiitv'),
            'social_facebook' => SiteSetting::get('social_facebook', 'https://facebook.com/kisiitv'),
            'social_youtube' => SiteSetting::get('social_youtube', 'https://youtube.com/@kisiitv'),
            'social_tiktok' => SiteSetting::get('social_tiktok', 'https://tiktok.com/@kisiitv'),
            'social_instagram' => SiteSetting::get('social_instagram', 'https://instagram.com/kisiitv'),
            'social_whatsapp' => SiteSetting::get('social_whatsapp', 'https://whatsapp.com/channel/kisiitv'),

            // Editorial Toggles
            'breaking_ticker_enabled' => SiteSetting::get('breaking_ticker_enabled', true),
            'enable_comments' => SiteSetting::get('enable_comments', true),
            'require_comment_moderation' => SiteSetting::get('require_comment_moderation', true),

            // Google Analytics & Search Console
            'google_analytics_enabled' => SiteSetting::get('google_analytics_enabled', false),
            'google_analytics_id' => SiteSetting::get('google_analytics_id', ''),
            'google_site_verification' => SiteSetting::get('google_site_verification', 'TVCSjm1BF7YJLuXkz3iBPhwy-ZqlctUpzsKAAh_V0LI'),
            'google_analytics_custom_script' => SiteSetting::get('google_analytics_custom_script', ''),

            // Google AdSense & Ad Placements
            'adsense_enabled' => SiteSetting::get('adsense_enabled', false),
            'adsense_auto_ads' => SiteSetting::get('adsense_auto_ads', true),
            'adsense_client_id' => SiteSetting::get('adsense_client_id', ''),
            'adsense_head_script' => SiteSetting::get('adsense_head_script', ''),
            'adsense_header_code' => SiteSetting::get('adsense_header_code', ''),
            'adsense_sidebar_code' => SiteSetting::get('adsense_sidebar_code', ''),
            'adsense_in_article_code' => SiteSetting::get('adsense_in_article_code', ''),
            'adsense_ads_txt' => SiteSetting::get('adsense_ads_txt', ''),

            // SMS Gateway (TextSMS Kenya / Africa's Talking / Webhook)
            'sms_driver' => SiteSetting::get('sms_driver', config('services.sms.driver', 'textsms')),
            'sms_textsms_api_key' => SiteSetting::get('sms_textsms_api_key', config('services.sms.textsms_api_key', '3dd298222c1ce227857fda7b42e97377')),
            'sms_textsms_partner_id' => SiteSetting::get('sms_textsms_partner_id', config('services.sms.textsms_partner_id', '14712')),
            'sms_textsms_shortcode' => SiteSetting::get('sms_textsms_shortcode', config('services.sms.textsms_shortcode', 'TextSMS')),
            'sms_textsms_host' => SiteSetting::get('sms_textsms_host', config('services.sms.textsms_host', 'sms.textsms.co.ke')),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update site settings, branding assets, and theme colors.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Branding
            'site_name' => 'required|string|max:100',
            'site_tagline' => 'nullable|string|max:150',
            'site_description' => 'nullable|string|max:500',
            'site_logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'site_logo_url' => 'nullable|url|max:500',
            'site_favicon_file' => 'nullable|image|mimes:ico,png,svg|max:1024',
            'site_favicon_url' => 'nullable|url|max:500',

            // Theme & Colors
            'primary_color' => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'accent_color' => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'header_bg_color' => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'ticker_bg_color' => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],

            // Contact & Desk
            'contact_email' => 'required|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'newsroom_address' => 'nullable|string|max:255',

            // Footer & Newsletter
            'footer_about' => 'nullable|string|max:1000',
            'footer_copyright' => 'nullable|string|max:255',
            'newsletter_title' => 'nullable|string|max:150',
            'newsletter_description' => 'nullable|string|max:300',

            // Social Channels
            'social_x' => 'nullable|url|max:255',
            'social_facebook' => 'nullable|url|max:255',
            'social_youtube' => 'nullable|url|max:255',
            'social_tiktok' => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'social_whatsapp' => 'nullable|url|max:255',

            // Editorial Toggles
            'breaking_ticker_enabled' => 'nullable|boolean',
            'enable_comments' => 'nullable|boolean',
            'require_comment_moderation' => 'nullable|boolean',

            // Google Analytics & Search Console
            'google_analytics_enabled' => 'nullable|boolean',
            'google_analytics_id' => 'nullable|string|max:50',
            'google_site_verification' => 'nullable|string|max:255',
            'google_analytics_custom_script' => 'nullable|string|max:5000',

            // Google AdSense
            'adsense_enabled' => 'nullable|boolean',
            'adsense_auto_ads' => 'nullable|boolean',
            'adsense_client_id' => 'nullable|string|max:100',
            'adsense_head_script' => 'nullable|string|max:5000',
            'adsense_header_code' => 'nullable|string|max:5000',
            'adsense_sidebar_code' => 'nullable|string|max:5000',
            'adsense_in_article_code' => 'nullable|string|max:5000',
            'adsense_ads_txt' => 'nullable|string|max:5000',

            // SMS Gateway
            'sms_driver' => 'nullable|string|in:textsms,africastalking,webhook,log',
            'sms_textsms_api_key' => 'nullable|string|max:255',
            'sms_textsms_partner_id' => 'nullable|string|max:100',
            'sms_textsms_shortcode' => 'nullable|string|max:50',
            'sms_textsms_host' => 'nullable|string|max:150',
        ]);

        // Handle Site Logo upload or URL
        if ($request->hasFile('site_logo_file')) {
            $destinationPath = public_path('uploads/branding');
            if (! File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            $fileName = 'logo_'.time().'.'.$request->file('site_logo_file')->getClientOriginalExtension();
            $request->file('site_logo_file')->move($destinationPath, $fileName);
            SiteSetting::set('site_logo', '/uploads/branding/'.$fileName, 'string', 'branding', 'Official site logo');
        } elseif ($request->filled('site_logo_url')) {
            SiteSetting::set('site_logo', $request->input('site_logo_url'), 'string', 'branding', 'Official site logo');
        }

        // Handle Favicon upload or URL
        if ($request->hasFile('site_favicon_file')) {
            $destinationPath = public_path('uploads/branding');
            if (! File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            $fileName = 'favicon_'.time().'.'.$request->file('site_favicon_file')->getClientOriginalExtension();
            $request->file('site_favicon_file')->move($destinationPath, $fileName);
            SiteSetting::set('site_favicon', '/uploads/branding/'.$fileName, 'string', 'branding', 'Site favicon icon');
        } elseif ($request->filled('site_favicon_url')) {
            SiteSetting::set('site_favicon', $request->input('site_favicon_url'), 'string', 'branding', 'Site favicon icon');
        }

        // Save Basic Text and Colors
        $keysToSave = [
            'site_name' => ['string', 'branding'],
            'site_tagline' => ['string', 'branding'],
            'site_description' => ['text', 'branding'],
            'primary_color' => ['string', 'theme'],
            'accent_color' => ['string', 'theme'],
            'header_bg_color' => ['string', 'theme'],
            'ticker_bg_color' => ['string', 'theme'],
            'contact_email' => ['string', 'general'],
            'contact_phone' => ['string', 'general'],
            'newsroom_address' => ['string', 'general'],
            'footer_about' => ['text', 'footer'],
            'footer_copyright' => ['string', 'footer'],
            'newsletter_title' => ['string', 'footer'],
            'newsletter_description' => ['string', 'footer'],
            'social_x' => ['string', 'social'],
            'social_facebook' => ['string', 'social'],
            'social_youtube' => ['string', 'social'],
            'social_tiktok' => ['string', 'social'],
            'social_instagram' => ['string', 'social'],
            'social_whatsapp' => ['string', 'social'],
            'google_analytics_id' => ['string', 'analytics'],
            'google_site_verification' => ['string', 'analytics'],
            'google_analytics_custom_script' => ['text', 'analytics'],
            'adsense_client_id' => ['string', 'monetization'],
            'adsense_head_script' => ['text', 'monetization'],
            'adsense_header_code' => ['text', 'monetization'],
            'adsense_sidebar_code' => ['text', 'monetization'],
            'adsense_in_article_code' => ['text', 'monetization'],
            'adsense_ads_txt' => ['text', 'monetization'],
            'sms_driver' => ['string', 'sms'],
            'sms_textsms_api_key' => ['string', 'sms'],
            'sms_textsms_partner_id' => ['string', 'sms'],
            'sms_textsms_shortcode' => ['string', 'sms'],
            'sms_textsms_host' => ['string', 'sms'],
        ];

        foreach ($keysToSave as $key => [$type, $group]) {
            if ($request->has($key)) {
                SiteSetting::set($key, $request->input($key), $type, $group);
            }
        }

        // Save Booleans
        SiteSetting::set('breaking_ticker_enabled', $request->boolean('breaking_ticker_enabled'), 'boolean', 'editorial');
        SiteSetting::set('enable_comments', $request->boolean('enable_comments'), 'boolean', 'editorial');
        SiteSetting::set('require_comment_moderation', $request->boolean('require_comment_moderation'), 'boolean', 'editorial');
        SiteSetting::set('google_analytics_enabled', $request->boolean('google_analytics_enabled'), 'boolean', 'analytics');
        SiteSetting::set('adsense_enabled', $request->boolean('adsense_enabled'), 'boolean', 'monetization');
        SiteSetting::set('adsense_auto_ads', $request->boolean('adsense_auto_ads'), 'boolean', 'monetization');

        $tab = $request->input('active_tab');
        $redirectParams = ($tab && $tab !== 'branding') ? ['tab' => $tab] : [];

        return redirect()->route('admin.settings.index', $redirectParams)
            ->with('success', 'Site settings, AdSense, Google Analytics, SMS gateway, and branding updated successfully!');
    }

    /**
     * Send a live test SMS from the admin backend to verify SMS gateway connectivity.
     */
    public function testSms(Request $request, SmsService $smsService): RedirectResponse
    {
        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:40'],
            'test_message' => ['nullable', 'string', 'max:320'],
        ]);

        $message = trim((string) ($validated['test_message'] ?? ''))
            ?: 'Kisii TV SMS Gateway Test: Your TextSMS integration is working properly!';

        $result = $smsService->sendWithDetails($validated['test_phone'], $message);

        if ($result['ok']) {
            return redirect()->route('admin.settings.index', ['tab' => 'sms'])
                ->with('success', "[{$result['provider']}] {$result['message']}");
        }

        return redirect()->route('admin.settings.index', ['tab' => 'sms'])
            ->with('error', "[{$result['provider']}] {$result['message']}");
    }

    /**
     * Send a live test email from the admin backend to verify SMTP configuration.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:150'],
        ]);

        try {
            Mail::raw(
                'Kisii TV SMTP Test: Your ticket@kisiitv.co.ke outgoing mail configuration is working properly!',
                function ($message) use ($validated): void {
                    $message->to($validated['test_email'])
                        ->subject('Kisii TV SMTP Email Test — Confirmed');
                }
            );

            return redirect()->route('admin.settings.index', ['tab' => 'sms'])
                ->with('success', "Test email sent successfully to {$validated['test_email']} via ".config('mail.default').' ('.config('mail.from.address').').');
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index', ['tab' => 'sms'])
                ->with('error', 'SMTP Test Failed: '.$e->getMessage());
        }
    }
}
