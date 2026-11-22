<?php

namespace Database\Seeders;

use App\Models\DonationCampaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class DonationCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();

        DonationCampaign::firstOrCreate(
            ['slug' => 'kisii-tv-investigative-journalism-fund'],
            [
                'title' => 'Kisii TV Investigative Journalism & County Accountability Fund',
                'summary' => 'Directly funding undercover investigations, public funds tracking, and grassroots accountability reporting across Kisii, Nyamira, and South Nyanza.',
                'description' => "Independent journalism is the cornerstone of democracy and community progress. At Kisii TV (Amandegere Y'Omogusii), we believe every citizen deserves uncompromised truth about how county resources are spent and how public projects are delivered.\n\nYour contribution to this campaign equips our investigative reporters with state-of-the-art field recorders, protective gear, forensic auditing tools, and travel logistics to verify claims on the ground.",
                'target_amount' => 500000.00,
                'currency' => 'KES',
                'cover_image' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
                'is_featured' => true,
                'created_by' => $admin?->id,
            ]
        );

        DonationCampaign::firstOrCreate(
            ['slug' => 'digital-broadcast-van-and-live-streaming-gear'],
            [
                'title' => 'Digital Broadcast Van & Live Field Streaming Equipment',
                'summary' => 'Help us upgrade our live broadcasting units to bring real-time 4K live streams of community barazas, church events, and cultural festivals direct to your screens.',
                'description' => 'Our viewers rely on Kisii TV for live digital streams of historic events, county assemblies, sports championships, and cultural moments. We are raising funds to procure reliable multi-camera backpack bonding units (LiveU) and satellite backup kits to ensure uninterrupted live coverage even in remote rural areas.',
                'target_amount' => 1200000.00,
                'currency' => 'KES',
                'cover_image' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=1200&auto=format&fit=crop&q=80',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'is_featured' => false,
                'created_by' => $admin?->id,
            ]
        );
    }
}
