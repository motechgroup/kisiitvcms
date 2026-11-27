<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Initialize Paystack checkout and return hosted authorization URL or error.
     *
     * @return array{url: ?string, error: ?string}
     */
    public function initializePaystack(Donation $donation, PaymentGateway $gateway): array
    {
        $secretKey = trim((string) $gateway->secret_key);

        if (empty($secretKey)) {
            return ['url' => null, 'error' => null];
        }

        try {
            $amountInCents = (int) round(((float) $donation->amount) * 100);
            $email = $donation->donor_email ?: 'supporter@kisiitv.co.ke';

            $callbackUrl = trim((string) $gateway->callback_url)
                ?: route('donations.callback', ['gateway' => 'paystack', 'donation' => $donation->transaction_reference]);

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
                return ['url' => $response->json('data.authorization_url'), 'error' => null];
            }

            $error = $response->json('message') ?? 'Paystack could not initialize checkout.';
            Log::warning('Paystack initialization response failed', ['body' => $response->json()]);

            return ['url' => null, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('Paystack initialization exception', ['error' => $e->getMessage()]);

            return ['url' => null, 'error' => 'Could not connect to Paystack. Please try again.'];
        }
    }

    /**
     * Initialize Flutterwave checkout and return hosted payment link or error.
     *
     * @return array{url: ?string, error: ?string}
     */
    public function initializeFlutterwave(Donation $donation, PaymentGateway $gateway): array
    {
        $secretKey = trim((string) $gateway->secret_key);

        if (empty($secretKey)) {
            return ['url' => null, 'error' => null];
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
                return ['url' => $response->json('data.link'), 'error' => null];
            }

            $error = $response->json('message') ?? 'Flutterwave could not initialize checkout.';
            Log::warning('Flutterwave initialization response failed', ['body' => $response->json()]);

            return ['url' => null, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('Flutterwave initialization exception', ['error' => $e->getMessage()]);

            return ['url' => null, 'error' => 'Could not connect to Flutterwave. Please try again.'];
        }
    }

    /**
     * Initialize Paystack hosted checkout for any generic payment (e.g., event tickets).
     *
     * @param  array<string, mixed>  $metadata
     * @return array{url: ?string, error: ?string}
     */
    public function initializePaystackPayment(
        PaymentGateway $gateway,
        float $amount,
        string $email,
        string $reference,
        string $callbackUrl,
        array $metadata = []
    ): array {
        $secretKey = trim((string) $gateway->secret_key);

        if ($secretKey === '') {
            return ['url' => null, 'error' => null];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($secretKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $email,
                    'amount' => (int) round($amount * 100),
                    'currency' => $gateway->currency ?: 'KES',
                    'reference' => $reference,
                    'callback_url' => $callbackUrl,
                    'metadata' => $metadata,
                ]);

            if ($response->successful() && $response->json('data.authorization_url')) {
                return ['url' => $response->json('data.authorization_url'), 'error' => null];
            }

            $error = $response->json('message') ?? 'Paystack could not initialize checkout.';
            Log::warning('Paystack ticket initialization failed', ['body' => $response->json()]);

            return ['url' => null, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('Paystack ticket initialization exception', ['error' => $e->getMessage()]);

            return ['url' => null, 'error' => 'Could not connect to Paystack. Please try again.'];
        }
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
