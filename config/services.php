<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bakong' => [
        'base_url' => env('BAKONG_PROD_BASE_API_URL', 'https://api-bakong.nbc.gov.kh/v1'),
        'account_username' => env('BAKONG_ACCOUNT_USERNAME'),
        'account_name' => env('BAKONG_ACCOUNT_NAME', 'CEC Electronic'),
        'access_token' => env('BAKONG_ACCESS_TOKEN'),
        'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),
        // How long a generated KHQR stays payable before it closes.
        'qr_expiry_seconds' => (int) env('BAKONG_QR_EXPIRY_SECONDS', 90),
        // Bakong caps check_transaction_by_md5 at ~100 requests/day for the
        // whole account. Keep a safety margin below that so the app never
        // gets everyone's pending orders stuck until the quota resets.
        'daily_check_limit' => (int) env('BAKONG_DAILY_CHECK_LIMIT', 90),
    ],

];
