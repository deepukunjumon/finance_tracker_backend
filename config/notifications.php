<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel
    |--------------------------------------------------------------------------
    */

    'default_channel' => env('NOTIFICATION_CHANNEL', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Channel Configurations
    |--------------------------------------------------------------------------
    */

    'channels' => [

        'email' => [
            'provider'      => env('EMAIL_PROVIDER', 'resend'),
            'from_address'  => env('MAIL_FROM_ADDRESS', 'no-reply@yourdomain.com'),
            'from_name'     => env('MAIL_FROM_NAME', 'Finance Tracker'),
        ],

        'sms' => [
            'provider' => env('SMS_PROVIDER', 'log'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'resend' => [
            'api_key' => env('RESEND_API_KEY'),
            'api_url' => env('RESEND_API_URL'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */

    'large_transaction_threshold' => env('LARGE_TRANSACTION_THRESHOLD', 10000),

];
