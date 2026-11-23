<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Initialize Paystack checkout and return hosted authorization URL.
     */
    public function initializePaystack(Donation $donation, PaymentGateway $gateway): ?string
    {
        $secretKey = trim((string) $gateway->secret_key);

        if (empty($secretKey)) {
            return null;
        }

        try {
            $amountInCents = (int) round(((float) $donation->amount) * 100);
            $email = $donation->donor_email ?: 'supporter@kisiitv.co.ke';

            $callbackUrl = route('donations.callback', ['gateway' => 'paystack', 'donation' => $donation->transaction_reference]);

            $response = Http::timeout(15)
                ->withToken($secretKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $email,
                    'amount' => $amountInCents,
                    'currency' => $gateway->currency ?: 'KES',
                    'reference' => $donation->transaction_reference,
                    'callback_url' => $callbackUrl,
                    'metadata' => [
                        'donor_name' => $donation->donor_name,
                        'donor_phone' => $donation->donor_phone,
                        'campaign_id' => $donation->campaign_id,
                        'live_stream_id' => $donation->live_stream_id,
                    ],
                ]);

            if ($response->successful() && $response->json('data.authorization_url')) {
                return $response->json('data.authorization_url');
            }

            Log::warning('Paystack initialization response failed', ['body' => $response->json()]);
        } catch (\Throwable $e) {
            Log::error('Paystack initialization exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Initialize Flutterwave checkout and return hosted payment link.
     */
    public function initializeFlutterwave(Donation $donation, PaymentGateway $gateway): ?string
    {
        $secretKey = trim((string) $gateway->secret_key);

        if (empty($secretKey)) {
            return null;
        }

        try {
            $callbackUrl = route('donations.callback', ['gateway' => 'flutterwave', 'donation' => $donation->transaction_reference]);
            $email = $donation->donor_email ?: 'supporter@kisiitv.co.ke';

            $response = Http::timeout(15)
                ->withToken($secretKey)
                ->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $donation->transaction_reference,
                    'amount' => (float) $donation->amount,
                    'currency' => $gateway->currency ?: 'KES',
                    'redirect_url' => $callbackUrl,
                    'customer' => [
                        'email' => $email,
                        'phonenumber' => $donation->donor_phone ?: '',
                        'name' => $donation->donor_name ?: 'Kisii TV Supporter',
                    ],
                    'customizations' => [
                        'title' => 'Support Kisii TV',
                        'description' => 'Contribution for '.($donation->campaign?->title ?? 'Kisii TV Newsroom'),
                        'logo' => asset('images/kisii-tv-logo.png'),
                    ],
                ]);

            if ($response->successful() && $response->json('data.link')) {
                return $response->json('data.link');
            }

            Log::warning('Flutterwave initialization response failed', ['body' => $response->json()]);
        } catch (\Throwable $e) {
            Log::error('Flutterwave initialization exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Verify Paystack transaction by reference.
     */
    public function verifyPaystack(string $reference, PaymentGateway $gateway): bool
    {
        $secretKey = $gateway->secret_key;
        if (empty($secretKey)) {
            return true;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($secretKey)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful() && $response->json('data.status') === 'success') {
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Paystack verification error', ['error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Verify Flutterwave transaction by ID.
     */
    public function verifyFlutterwave(string $transactionId, PaymentGateway $gateway): bool
    {
        $secretKey = $gateway->secret_key;
        if (empty($secretKey)) {
            return true;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($secretKey)
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            if ($response->successful() && $response->json('data.status') === 'successful') {
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Flutterwave verification error', ['error' => $e->getMessage()]);
        }

        return false;
    }
}
