<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

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
        'callback_url',
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

    /**
     * Encrypt secret_key at rest and safely decrypt on read.
     */
    protected function secretKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::decryptCredential($value),
            set: fn (?string $value) => self::encryptCredential($value),
        );
    }

    /**
     * Encrypt passkey_or_webhook_secret at rest and safely decrypt on read.
     */
    protected function passkeyOrWebhookSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::decryptCredential($value),
            set: fn (?string $value) => self::encryptCredential($value),
        );
    }

    protected static function encryptCredential(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }

    protected static function decryptCredential(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
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
                'public_key' => '9a7lKfNJxw0eIiCay8OqFHeBGNXSfBLQTxWRACneeKEfSBjD',
                'secret_key' => 'GdtbnHA4Wl7uzyB4bNNlDPMP1B2fmSv3AvynXqvmIkoXexMQn3azKN3SVnczPSS8',
                'passkey_or_webhook_secret' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
                'shortcode_or_merchant_id' => '174379',
                'account_reference' => 'KISIITV',
                'callback_url' => 'https://kisiitv.co.ke/donations/webhook/mpesa',
                'instructions' => 'Enter your M-Pesa phone number (07xx or 2547xx) to receive an instant STK push prompt on your phone, or use Paybill 174379 Account KISIITV.',
                'display_order' => 1,
            ],
            [
                'name' => 'Paystack',
                'code' => 'paystack',
                'provider_label' => 'Paystack (Card, Mobile Money & Bank)',
                'description' => 'Accept Visa, Mastercard, Apple Pay, and M-Pesa payments seamlessly powered by Paystack.',
                'is_active' => true,
                'is_test_mode' => false,
                'currency' => 'KES',
                'public_key' => config('services.paystack.public_key'),
                'secret_key' => config('services.paystack.secret_key'),
                'passkey_or_webhook_secret' => config('services.paystack.restricted_key'),
                'callback_url' => config('services.paystack.callback_url', 'https://kisiitv.co.ke/donations/callback/paystack'),
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

        self::syncPaystackEnvCredentials();
    }

    /**
     * Backfill empty Paystack credentials from config/.env and store them encrypted.
     */
    public static function syncPaystackEnvCredentials(): void
    {
        $paystack = self::where('code', 'paystack')->first();

        if (! $paystack) {
            return;
        }

        $publicKey = config('services.paystack.public_key') ?: env('PAYSTACK_PUBLIC_KEY');
        $secretKey = config('services.paystack.secret_key') ?: env('PAYSTACK_SECRET_KEY');
        $restrictedKey = config('services.paystack.restricted_key') ?: env('PAYSTACK_RESTRICTED_KEY');
        $callbackUrl = config('services.paystack.callback_url') ?: env('PAYSTACK_CALLBACK_URL', 'https://kisiitv.co.ke/donations/callback/paystack');

        $updates = [];

        if (blank($paystack->public_key) && filled($publicKey)) {
            $updates['public_key'] = $publicKey;
        }

        if (blank($paystack->secret_key) && filled($secretKey)) {
            $updates['secret_key'] = $secretKey;
        }

        if (blank($paystack->passkey_or_webhook_secret) && filled($restrictedKey)) {
            $updates['passkey_or_webhook_secret'] = $restrictedKey;
        }

        if (blank($paystack->callback_url) && filled($callbackUrl)) {
            $updates['callback_url'] = $callbackUrl;
        }

        if ($updates !== []) {
            $paystack->update($updates);
        }
    }
}
