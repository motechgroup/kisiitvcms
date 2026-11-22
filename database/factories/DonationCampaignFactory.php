<?php

namespace Database\Factories;

use App\Models\DonationCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DonationCampaign>
 */
class DonationCampaignFactory extends Factory
{
    protected $model = DonationCampaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'summary' => fake()->paragraph(2),
            'description' => fake()->paragraphs(3, true),
            'target_amount' => fake()->randomElement([50000, 100000, 250000, 500000, 1000000]),
            'currency' => 'KES',
            'cover_image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1200&auto=format&fit=crop&q=80',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(25),
            'is_active' => true,
            'is_featured' => true,
            'created_by' => User::factory(),
        ];
    }
}
