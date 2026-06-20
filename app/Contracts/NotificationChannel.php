<?php

namespace App\Contracts;

interface NotificationChannel
{
    public function send(string $to, string $subject, string $body, array $options = []): bool;
}
