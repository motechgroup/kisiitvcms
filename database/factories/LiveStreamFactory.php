<?php

namespace Database\Factories;

use App\Models\LiveStream;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LiveStream>
 */
class LiveStreamFactory extends Factory
{
    protected $model = LiveStream::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->paragraph(),
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
            'thumbnail' => null,
            'status' => 'live',
            'is_active' => true,
            'is_featured' => true,
            'donations_enabled' => true,
            'donation_goal' => 50000.00,
            'donation_call_to_action' => 'Support Kisii TV Live Independent Journalism',
            'viewer_count' => fake()->numberBetween(120, 4500),
            'started_at' => now(),
        ];
    }
}
