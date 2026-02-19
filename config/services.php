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

    'whatsapp' => [
        'base_url' => env('WHATSAPP_GATEWAY_URL'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN'),
        'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN'),
        'timeout' => env('WHATSAPP_HTTP_TIMEOUT', 10),
        'connect_timeout' => env('WHATSAPP_HTTP_CONNECT_TIMEOUT', 5),
    ],

    'telegram' => [
        'base_url' => env('TELEGRAM_GATEWAY_URL'),
        'token' => env('TELEGRAM_GATEWAY_TOKEN'),
        'webhook_token' => env('TELEGRAM_WEBHOOK_TOKEN'),
        'timeout' => env('TELEGRAM_HTTP_TIMEOUT', 10),
        'connect_timeout' => env('TELEGRAM_HTTP_CONNECT_TIMEOUT', 5),
    ],

];
