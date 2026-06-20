<?php

namespace App\Services\Channels;

use App\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannel
{
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        Log::info('[SMS Placeholder] Would send SMS.', [
            'to'      => $to,
            'subject' => $subject,
            'body'    => strip_tags($body),
        ]);

        return true;
    }
}
