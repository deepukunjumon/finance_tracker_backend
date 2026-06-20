<?php

namespace App\Services\Channels;

use App\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannel
{
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $apiKey   = config('notifications.providers.resend.api_key');
        $apiUrl   = config('notifications.providers.resend.api_url');
        $fromAddr = config('notifications.channels.email.from_address');
        $fromName = config('notifications.channels.email.from_name');

        if (! $apiKey) {
            Log::warning('Resend API key not configured. Email not sent.', compact('to', 'subject'));
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post($apiUrl, [
                'from'    => "{$fromName} <{$fromAddr}>",
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $body,
            ]);

            if ($response->successful()) {
                Log::info('Email sent via Resend.', ['to' => $to, 'subject' => $subject, 'id' => $response->json('id')]);
                return true;
            }

            Log::error('Resend API error.', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Email send failed.', ['error' => $e->getMessage(), 'to' => $to]);
            return false;
        }
    }
}
