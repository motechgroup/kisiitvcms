<?php

namespace Database\Seeders;

use App\Models\LiveStream;
use Illuminate\Database\Seeder;

class LiveStreamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LiveStream::updateOrCreate(
            ['slug' => 'kisii-tv-24-7-live-broadcast'],
            [
                'title' => 'Kisii TV 24/7 Live Broadcast — Breaking News & Gusii Regional Desk',
                'description' => 'Watch Kisii TV (Amandegere Y\'Omogusii) live coverage of breaking national headlines, county assembly proceedings, grassroots devolution forums, and cultural broadcasts.',
                'platform' => 'youtube',
                'stream_url' => 'https://www.youtube.com/watch?v=jfKfPfyJRdk',
                'embed_url' => 'https://www.youtube.com/embed/jfKfPfyJRdk?rel=0',
                'status' => 'live',
                'is_active' => true,
                'is_featured' => true,
                'donations_enabled' => true,
                'donation_goal' => 100000.00,
                'donation_call_to_action' => 'Support Independent Journalism & Live Community Coverage on Kisii TV',
                'viewer_count' => 1420,
                'started_at' => now()->subHours(2),
            ]
        );
    }
}
