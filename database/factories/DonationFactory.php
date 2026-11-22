<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'live_stream_id' => null,
            'payment_gateway_id' => null,
            'gateway_code' => 'mpesa',
            'transaction_reference' => 'KTV-DON-'.strtoupper(Str::random(10)),
            'external_reference' => null,
            'donor_name' => fake()->name(),
            'donor_email' => fake()->safeEmail(),
            'donor_phone' => '2547'.fake()->numerify('########'),
            'amount' => fake()->randomElement([100, 500, 1000, 2500, 5000]),
            'currency' => 'KES',
            'message' => fake()->sentence(),
            'is_anonymous' => false,
            'status' => 'completed',
            'paid_at' => now(),
        ];
    }
}
