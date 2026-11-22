<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    /**
     * Format a Kenyan phone number into 254XXXXXXXXX format.
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        } elseif (str_starts_with($phone, '254')) {
            // Already standard format
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254'.$phone;
        }

        return $phone;
    }

    /**
     * Initiate a Lipa na M-Pesa Online STK Push.
     *
     * @return array<string, mixed>
     */
    public function initiateStkPush(Donation $donation, ?PaymentGateway $gateway = null): array
    {
        $gateway = $gateway ?? $donation->paymentGateway ?? PaymentGateway::where('code', 'mpesa')->first();

        if (! $gateway) {
            return [
                'success' => false,
                'message' => 'M-Pesa payment gateway configuration not found.',
            ];
        }

        $phone = self::formatPhoneNumber($donation->donor_phone ?: '');
        if (strlen($phone) < 12) {
            return [
                'success' => false,
                'message' => 'Please provide a valid Safaricom phone number (e.g. 0712345678).',
            ];
        }

        $amount = (int) ceil((float) $donation->amount);
        $shortcode = $gateway->shortcode_or_merchant_id ?: '174379';
        $consumerKey = $gateway->public_key;
        $consumerSecret = $gateway->secret_key;
        $passkey = $gateway->passkey_or_webhook_secret;

        // If credentials are empty or marked as test mode, run robust simulation
        $isLiveConfigured = ! empty($consumerKey) && ! empty($consumerSecret) && ! empty($passkey) && ! $gateway->is_test_mode;

        if (! $isLiveConfigured) {
            $simulatedCheckoutId = 'ws_CO_'.date('YmdHis').'_'.rand(1000, 9999);

            $meta = $donation->metadata ?? [];
            $meta['checkout_request_id'] = $simulatedCheckoutId;
            $meta['stk_phone'] = $phone;
            $meta['stk_mode'] = 'simulation';
            $meta['stk_initiated_at'] = now()->toDateTimeString();
            $donation->update(['metadata' => $meta]);

            return [
                'success' => true,
                'message' => "M-Pesa prompt sent to {$phone}. Please enter your M-Pesa PIN on your phone.",
                'checkout_request_id' => $simulatedCheckoutId,
                'is_simulation' => true,
            ];
        }

        // Live Safaricom Daraja API
        try {
            $authUrl = $gateway->is_test_mode
                ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

            $tokenResponse = Http::timeout(15)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get($authUrl);

            if (! $tokenResponse->successful()) {
                Log::error('M-Pesa Token Error', ['body' => $tokenResponse->body()]);

                return [
                    'success' => false,
                    'message' => 'Could not connect to Safaricom Daraja API. Please check your credentials or use manual Paybill.',
                ];
            }

            $token = $tokenResponse->json('access_token');
            $timestamp = date('YmdHis');
            $password = base64_encode($shortcode.$passkey.$timestamp);

            $stkUrl = $gateway->is_test_mode
                ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

            $callbackUrl = route('donations.webhook', 'mpesa');

            $payload = [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $gateway->account_reference ?: 'KISIITV',
                'TransactionDesc' => 'Kisii TV Support '.$donation->transaction_reference,
            ];

            $stkResponse = Http::timeout(20)
                ->withToken($token)
                ->post($stkUrl, $payload);

            $stkData = $stkResponse->json();

            if ($stkResponse->successful() && ($stkData['ResponseCode'] ?? null) === '0') {
                $checkoutRequestId = $stkData['CheckoutRequestID'] ?? null;

                $meta = $donation->metadata ?? [];
                $meta['checkout_request_id'] = $checkoutRequestId;
                $meta['stk_phone'] = $phone;
                $meta['stk_mode'] = 'live';
                $meta['stk_initiated_at'] = now()->toDateTimeString();
                $donation->update(['metadata' => $meta]);

                return [
                    'success' => true,
                    'message' => "M-Pesa prompt sent to {$phone}. Please enter your M-Pesa PIN on your phone.",
                    'checkout_request_id' => $checkoutRequestId,
                    'is_simulation' => false,
                ];
            }

            $errorMsg = $stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? 'Failed to initiate STK push.';

            return [
                'success' => false,
                'message' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK Push Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Network error connecting to Safaricom. Please try again or use manual Paybill.',
            ];
        }
    }
}
