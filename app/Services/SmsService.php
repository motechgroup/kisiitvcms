<?php

namespace App\Services;

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
     * Send an SMS message using the configured driver (africastalking, webhook, or log).
     */
    public function send(string $to, string $message): bool
    {
        $recipient = $this->normalizePhone($to);
        $driver = (string) config('services.sms.driver', 'log');

        if ($driver === 'africastalking') {
            return $this->sendViaAfricasTalking($recipient, $message);
        }

        if ($driver === 'webhook') {
            return $this->sendViaWebhook($recipient, $message);
        }

        Log::info('SMS dispatched (log driver)', [
            'to' => $recipient,
            'message' => $message,
        ]);

        return true;
    }

    protected function sendViaAfricasTalking(string $recipient, string $message): bool
    {
        $username = trim((string) config('services.sms.africastalking_username'));
        $apiKey = trim((string) config('services.sms.africastalking_api_key'));
        $senderId = trim((string) config('services.sms.sender_id'));

        if ($username === '' || $apiKey === '') {
            Log::warning('Africa\'s Talking SMS credentials missing; falling back to log.', [
                'to' => $recipient,
                'message' => $message,
            ]);

            return false;
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
                return true;
            }

            Log::warning('Africa\'s Talking SMS failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Africa\'s Talking SMS exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function sendViaWebhook(string $recipient, string $message): bool
    {
        $url = trim((string) config('services.sms.webhook_url'));
        $apiKey = trim((string) config('services.sms.webhook_api_key'));
        $senderId = trim((string) config('services.sms.sender_id', 'KISIITV'));

        if ($url === '') {
            Log::warning('SMS webhook URL missing; falling back to log.', [
                'to' => $recipient,
                'message' => $message,
            ]);

            return false;
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
                return true;
            }

            Log::warning('SMS webhook failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SMS webhook exception', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
