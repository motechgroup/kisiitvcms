<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'provider_label',
        'description',
        'is_active',
        'is_test_mode',
        'currency',
        'public_key',
        'secret_key',
        'passkey_or_webhook_secret',
        'shortcode_or_merchant_id',
        'account_reference',
        'instructions',
        'display_order',
    ];

    protected $hidden = [
        'secret_key',
        'passkey_or_webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Get the public logo asset URL for the payment gateway, if available.
     */
    public function logoUrl(): ?string
    {
        return match ($this->code) {
            'mpesa' => asset('images/gateways/mpesa.jpg'),
            'paystack' => asset('images/gateways/paystack.png'),
            default => null,
        };
    }

    /**
     * Default gateways supported by the platform.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultGateways(): array
    {
        return [
            [
                'name' => 'M-Pesa (Safaricom Daraja / Paybill)',
                'code' => 'mpesa',
                'provider_label' => 'Lipa na M-Pesa (STK Push & Paybill)',
                'description' => 'Instant mobile money donations via Safaricom M-Pesa Express (STK Push) or Paybill/Till Number.',
                'is_active' => true,
                'is_test_mode' => true,
                'currency' => 'KES',
                'shortcode_or_merchant_id' => '174379',
                'account_reference' => 'KISIITV',
                'instructions' => 'Enter your M-Pesa phone number (07xx or 2547xx) to receive an instant STK push prompt on your phone, or use Paybill 174379 Account KISIITV.',
                'display_order' => 1,
            ],
            [
                'name' => 'Paystack',
                'code' => 'paystack',
                'provider_label' => 'Paystack (Card, Mobile Money & Bank)',
                'description' => 'Accept Visa, Mastercard, Apple Pay, and M-Pesa payments seamlessly powered by Paystack.',
                'is_active' => true,
                'is_test_mode' => true,
                'currency' => 'KES',
                'instructions' => 'Complete your contribution securely using Visa, Mastercard, or Mobile Money via Paystack Checkout.',
                'display_order' => 2,
            ],
            [
                'name' => 'PayPal',
                'code' => 'paypal',
                'provider_label' => 'PayPal (International Cards & Wallet)',
                'description' => 'Support Kisii TV from anywhere in the diaspora using PayPal balance or international debit/credit cards.',
                'is_active' => true,
                'is_test_mode' => true,
                'currency' => 'USD',
                'instructions' => 'Donate securely using your PayPal account or international credit/debit card.',
                'display_order' => 3,
            ],
            [
                'name' => 'Flutterwave',
                'code' => 'flutterwave',
                'provider_label' => 'Flutterwave (Cards, M-Pesa & Pan-African Payments)',
                'description' => 'Pan-African & global payment processing supporting cards, M-Pesa, Airtel Money, and bank transfers.',
                'is_active' => true,
                'is_test_mode' => true,
                'currency' => 'KES',
                'instructions' => 'Pay seamlessly with Card, M-Pesa, or Mobile Money via Flutterwave.',
                'display_order' => 4,
            ],
        ];
    }

    /**
     * Ensure the four core payment gateways exist in the database.
     */
    public static function ensureDefaultsExist(): void
    {
        foreach (self::defaultGateways() as $gateway) {
            self::firstOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }
    }
}
