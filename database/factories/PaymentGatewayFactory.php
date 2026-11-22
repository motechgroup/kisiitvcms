<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->word();

        return [
            'name' => ucfirst($code).' Gateway',
            'code' => $code,
            'provider_label' => ucfirst($code).' Payments',
            'description' => fake()->sentence(),
            'is_active' => true,
            'is_test_mode' => true,
            'currency' => 'KES',
            'public_key' => 'pk_test_'.fake()->sha1(),
            'secret_key' => 'sk_test_'.fake()->sha1(),
            'display_order' => 1,
        ];
    }
}
