<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Normalize a Kenyan or international phone number to E.164 format (+254...).
     */
    public function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        if (str_starts_with($clean, '0') && strlen($clean) === 10) {
            return '+254'.substr($clean, 1);
        }

        if (str_starts_with($clean, '254') && ! str_starts_with($clean, '+')) {
            return '+'.$clean;
        }

        if (str_starts_with($clean, '7') || str_starts_with($clean, '1')) {
            return '+254'.$clean;
        }

        return $clean;
    }

    /**
     * Send an SMS message using the configured driver and return true on success.
     */
    public function send(string $to, string $message): bool
    {
        return $this->sendWithDetails($to, $message)['ok'];
    }

    /**
     * Send an SMS message and return structured delivery status details (used for admin testing too).
     *
     * @return array{ok: bool, provider: string, message: string, raw: mixed}
     */
    public function sendWithDetails(string $to, string $message): array
    {
        $recipient = $this->normalizePhone($to);
        $driver = strtolower(trim((string) SiteSetting::get('sms_driver', config('services.sms.driver', 'textsms'))));

        return match ($driver) {
            'textsms' => $this->sendViaTextSms($recipient, $message),
            'africastalking' => $this->sendViaAfricasTalking($recipient, $message),
            'webhook' => $this->sendViaWebhook($recipient, $message),
            default => $this->sendViaLog($recipient, $message),
        };
    }

    /**
     * Send SMS via TextSMS (Kenya) Bulk SMS API (https://sms.textsms.co.ke/api/services/sendsms/).
     *
     * @return array{ok: bool, provider: string, message: string, raw: mixed}
     */
    protected function sendViaTextSms(string $recipient, string $message): array
    {
        $apiKey = trim((string) SiteSetting::get('sms_textsms_api_key', config('services.sms.textsms_api_key', '3dd298222c1ce227857fda7b42e97377')));
        $partnerId = trim((string) SiteSetting::get('sms_textsms_partner_id', config('services.sms.textsms_partner_id', '14712')));
        $shortcode = trim((string) SiteSetting::get('sms_textsms_shortcode', config('services.sms.textsms_shortcode', 'TextSMS')));
        $host = trim((string) SiteSetting::get('sms_textsms_host', config('services.sms.textsms_host', 'sms.textsms.co.ke')));

        if ($apiKey === '' || $partnerId === '' || $shortcode === '') {
            return [
                'ok' => false,
                'provider' => 'TextSMS (Kenya)',
                'message' => 'TextSMS API Key, Partner ID, and Shortcode are required.',
                'raw' => null,
            ];
        }

        $cleanHost = preg_replace('#^https?://#i', '', rtrim($host ?: 'sms.textsms.co.ke', '/'));
        $endpoint = "https://{$cleanHost}/api/services/sendsms/";
        $mobile = ltrim($recipient, '+');

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, [
                    'apikey' => $apiKey,
                    'partnerID' => $partnerId,
                    'message' => $message,
                    'shortcode' => $shortcode,
                    'mobile' => $mobile,
                ]);

            $json = $response->json();
            $firstResponse = is_array($json['responses'] ?? null) ? ($json['responses'][0] ?? null) : null;
            $responseCode = $firstResponse['response-code'] ?? ($json['response-code'] ?? null);
            $description = $firstResponse['response-description'] ?? ($json['response-description'] ?? $response->body());
            $messageId = $firstResponse['messageid'] ?? null;

            if ($response->successful() && (int) $responseCode === 200) {
                $detail = $messageId ? "Delivered to TextSMS (Message ID: {$messageId})." : 'Delivered to TextSMS.';

                return [
                    'ok' => true,
                    'provider' => 'TextSMS (Kenya)',
                    'message' => $detail,
                    'raw' => $json,
                ];
            }

            Log::warning('TextSMS Kenya API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'provider' => 'TextSMS (Kenya)',
                'message' => 'TextSMS error (code '.($responseCode ?? $response->status()).'): '.(is_string($description) ? $description : 'Request rejected.'),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('TextSMS Kenya exception', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'provider' => 'TextSMS (Kenya)',
                'message' => 'Connection error reaching TextSMS: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, provider: string, message: string, raw: mixed}
     */
    protected function sendViaAfricasTalking(string $recipient, string $message): array
    {
        $username = trim((string) config('services.sms.africastalking_username'));
        $apiKey = trim((string) config('services.sms.africastalking_api_key'));
        $senderId = trim((string) config('services.sms.sender_id'));

        if ($username === '' || $apiKey === '') {
            return [
                'ok' => false,
                'provider' => "Africa's Talking",
                'message' => "Africa's Talking username or API key is missing in .env.",
                'raw' => null,
            ];
        }

        $endpoint = $username === 'sandbox'
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        $payload = [
            'username' => $username,
            'to' => $recipient,
            'message' => $message,
        ];

        if ($senderId !== '') {
            $payload['from'] = $senderId;
        }

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'provider' => "Africa's Talking",
                    'message' => "SMS queued via Africa's Talking.",
                    'raw' => $response->json(),
                ];
            }

            Log::warning("Africa's Talking SMS failed", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'provider' => "Africa's Talking",
                'message' => "Africa's Talking returned HTTP {$response->status()}.",
                'raw' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error("Africa's Talking SMS exception", ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'provider' => "Africa's Talking",
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, provider: string, message: string, raw: mixed}
     */
    protected function sendViaWebhook(string $recipient, string $message): array
    {
        $url = trim((string) config('services.sms.webhook_url'));
        $apiKey = trim((string) config('services.sms.webhook_api_key'));
        $senderId = trim((string) config('services.sms.sender_id', 'KISIITV'));

        if ($url === '') {
            return [
                'ok' => false,
                'provider' => 'Webhook',
                'message' => 'SMS webhook URL is missing in .env.',
                'raw' => null,
            ];
        }

        try {
            $request = Http::timeout(15)->acceptJson();
            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($url, [
                'to' => $recipient,
                'sender_id' => $senderId,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'provider' => 'Webhook',
                    'message' => 'SMS dispatched via custom webhook.',
                    'raw' => $response->json(),
                ];
            }

            return [
                'ok' => false,
                'provider' => 'Webhook',
                'message' => "Webhook returned HTTP {$response->status()}.",
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'provider' => 'Webhook',
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, provider: string, message: string, raw: mixed}
     */
    protected function sendViaLog(string $recipient, string $message): array
    {
        Log::info('SMS dispatched (log driver)', [
            'to' => $recipient,
            'message' => $message,
        ]);

        return [
            'ok' => true,
            'provider' => 'Log Driver',
            'message' => "Logged SMS for {$recipient} (no external SMS sent in Log mode).",
            'raw' => null,
        ];
    }
}
