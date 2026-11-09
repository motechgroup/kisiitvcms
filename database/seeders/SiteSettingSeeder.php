<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General & Branding
            ['key' => 'site_name', 'value' => 'Kisii TV', 'type' => 'string', 'group' => 'general', 'description' => 'Official media organization name'],
            ['key' => 'site_tagline', 'value' => "Amandegere Y'Omogusii", 'type' => 'string', 'group' => 'general', 'description' => 'Editorial tagline'],
            ['key' => 'site_description', 'value' => "Kisii TV (Amandegere Y'Omogusii) is Kenya's premier digital newsroom and regional broadcaster, delivering fearless investigations, breaking headlines, Gusii cultural heritage, county devolution, politics, and East African affairs.", 'type' => 'text', 'group' => 'general', 'description' => 'Site meta description'],
            ['key' => 'site_keywords', 'value' => 'Kisii TV, Amandegere Y’omogusii, Kisii News, Kenya Breaking News, Kevin Ombwori, Gusii Politics, Nyamira News, Devolution Kenya, Gusii Culture, Kenya Television', 'type' => 'text', 'group' => 'general', 'description' => 'Target SEO keywords'],
            ['key' => 'contact_email', 'value' => 'kisiitelevision@gmail.com', 'type' => 'string', 'group' => 'general', 'description' => 'Editorial desk email'],
            ['key' => 'contact_phone', 'value' => '+254 726 547 363', 'type' => 'string', 'group' => 'general', 'description' => 'Newsroom hotline phone'],
            ['key' => 'newsroom_address', 'value' => 'Sansora Complex, Kisii Town & Nairobi Media Center, Kenya', 'type' => 'string', 'group' => 'general', 'description' => 'Bureau address'],

            // Footer
            ['key' => 'footer_about', 'value' => "Kisii TV (Amandegere Y'Omogusii) is an independent Kenyan broadcast and digital news organization committed to fearless investigative reporting, authoritative political analysis, comprehensive county governance coverage, cultural storytelling, and live national affairs.", 'type' => 'text', 'group' => 'footer', 'description' => 'Footer mission statement'],
            ['key' => 'footer_copyright', 'value' => 'Kisii TV Media Network (Amandegere Y’Omogusii). All rights reserved.', 'type' => 'string', 'group' => 'footer', 'description' => 'Footer copyright text'],

            // Localization
            ['key' => 'default_timezone', 'value' => 'Africa/Nairobi', 'type' => 'string', 'group' => 'localization', 'description' => 'Newsroom publication timezone'],
            ['key' => 'default_currency', 'value' => 'KES', 'type' => 'string', 'group' => 'localization', 'description' => 'Monetization reporting currency'],
            ['key' => 'date_format', 'value' => 'M d, Y H:i T', 'type' => 'string', 'group' => 'localization', 'description' => 'Default publication timestamp format'],

            // Real Kisii TV Social Channels Scraped Online
            ['key' => 'social_youtube', 'value' => 'https://www.youtube.com/@kisiitv', 'type' => 'string', 'group' => 'social', 'description' => 'Official YouTube Channel (@kisiitv)'],
            ['key' => 'social_facebook', 'value' => 'https://www.facebook.com/KisiitvAmandegere', 'type' => 'string', 'group' => 'social', 'description' => 'Official Facebook Page (Kisii TV Amandegere Y’omogusii)'],
            ['key' => 'social_tiktok', 'value' => 'https://www.tiktok.com/@kevinombworisnr', 'type' => 'string', 'group' => 'social', 'description' => 'Official TikTok Channel (@kevinombworisnr)'],
            ['key' => 'social_x', 'value' => 'https://x.com/kisiitv', 'type' => 'string', 'group' => 'social', 'description' => 'Official X/Twitter Account (@kisiitv)'],
            ['key' => 'social_instagram', 'value' => 'https://www.instagram.com/kisiitv', 'type' => 'string', 'group' => 'social', 'description' => 'Official Instagram Account (@kisiitv)'],
            ['key' => 'social_whatsapp', 'value' => 'https://wa.me/254726547363', 'type' => 'string', 'group' => 'social', 'description' => 'Official WhatsApp Hotline & Community Channel'],

            // Editorial Defaults
            ['key' => 'enable_comments', 'value' => '1', 'type' => 'boolean', 'group' => 'editorial', 'description' => 'Enable reader comments globally'],
            ['key' => 'require_comment_moderation', 'value' => '1', 'type' => 'boolean', 'group' => 'editorial', 'description' => 'Require moderator approval for comments'],
            ['key' => 'breaking_ticker_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'editorial', 'description' => 'Display breaking news ticker banner'],

            // Theme Colors Defaults
            ['key' => 'primary_color', 'value' => '#1b52bc', 'type' => 'string', 'group' => 'branding', 'description' => 'Primary Brand Color'],
            ['key' => 'accent_color', 'value' => '#d9252a', 'type' => 'string', 'group' => 'branding', 'description' => 'Accent Red Color'],
            ['key' => 'header_bg_color', 'value' => '#0a1c3d', 'type' => 'string', 'group' => 'branding', 'description' => 'Header Background Color'],
            ['key' => 'ticker_bg_color', 'value' => '#d9252a', 'type' => 'string', 'group' => 'branding', 'description' => 'Breaking Ticker Color'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
            Cache::forget("site_setting_{$setting['key']}");
        }
    }
}
