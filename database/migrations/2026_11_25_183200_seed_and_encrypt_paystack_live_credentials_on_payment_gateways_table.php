<?php

use App\Models\PaymentGateway;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        PaymentGateway::ensureDefaultsExist();

        $paystack = PaymentGateway::where('code', 'paystack')->first();
        if ($paystack) {
            $updates = [
                'callback_url' => config('services.paystack.callback_url', 'https://kisiitv.co.ke/donations/callback/paystack'),
                'is_active' => true,
                'is_test_mode' => false,
            ];

            if (filled(config('services.paystack.public_key'))) {
                $updates['public_key'] = config('services.paystack.public_key');
            }

            if (filled(config('services.paystack.secret_key'))) {
                $updates['secret_key'] = config('services.paystack.secret_key');
            }

            if (filled(config('services.paystack.restricted_key'))) {
                $updates['passkey_or_webhook_secret'] = config('services.paystack.restricted_key');
            }

            $paystack->update($updates);
        }

        // Re-save existing gateways so any plaintext secrets are encrypted at rest.
        foreach (PaymentGateway::all() as $gateway) {
            if ($gateway->code === 'paystack') {
                continue;
            }

            $gateway->update([
                'secret_key' => $gateway->secret_key,
                'passkey_or_webhook_secret' => $gateway->passkey_or_webhook_secret,
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
