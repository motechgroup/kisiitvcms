<?php

use App\Models\Event;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $event = Event::firstOrCreate(
            ['slug' => 'kisii-tv-cultural-music-festival-2026'],
            [
                'title' => 'Kisii TV Cultural & Music Festival 2026',
                'summary' => 'Join Kisii TV live at Gusii Stadium for an unforgettable celebration of Gusii heritage, top Kenyan live bands, comedy, and cultural showcases.',
                'description' => "Experience the biggest live broadcast and cultural gathering in the Gusii region!\n\nHighlights include:\n• Live performances from top Gusii & national Kenyan artists\n• Traditional Ekegusii dance, comedy & fashion showcase\n• Live Kisii TV studio broadcast & meet-and-greet with your favorite anchors\n• Family food court, VIP lounge & curated hospitality\n\nDoors open at 2:00 PM EAT. Your digital QR ticket link will be sent instantly via SMS and Email upon booking.",
                'venue' => 'Gusii Stadium',
                'city' => 'Kisii Town',
                'cover_image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1400&q=80',
                'flyer_image' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=900&q=80',
                'starts_at' => now()->addDays(14)->setTime(14, 0),
                'ends_at' => now()->addDays(14)->setTime(22, 0),
                'currency' => 'KES',
                'is_published' => true,
                'is_featured' => true,
            ]
        );

        if ($event->ticketTypes()->doesntExist()) {
            $event->ticketTypes()->createMany([
                [
                    'name' => 'Early Bird Pass',
                    'description' => 'Limited discounted general admission ticket',
                    'price' => 500,
                    'quantity_available' => 100,
                    'is_active' => true,
                    'display_order' => 0,
                ],
                [
                    'name' => 'Regular Admission',
                    'description' => 'Standard full-day stadium entry & live concert access',
                    'price' => 1000,
                    'quantity_available' => 300,
                    'is_active' => true,
                    'display_order' => 1,
                ],
                [
                    'name' => 'VIP Lounge Experience',
                    'description' => 'Front-row VIP tent seating, refreshments & backstage meet-and-greet',
                    'price' => 3000,
                    'quantity_available' => 50,
                    'is_active' => true,
                    'display_order' => 2,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
