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

    'openrouter' => [
        'key' => env('OPENROUTER_KEY'),
        'model' => env('AI_MODEL', 'deepseek/deepseek-v3-base:free'),
        'endpoint' => env('OPENROUTER_ENDPOINT', 'https://openrouter.ai/api/v1/chat'),
    ],

    'turnstile' => [
        'site_key' => env('VITE_TURNSTILE_SITE_KEY'),
        'secret'   => env('TURNSTILE_SECRET_KEY'),
        // Disabled on local (.test / Herd) — only enforced when a secret is set
        // and the app is not running in the local environment.
        'enabled'  => env('APP_ENV') !== 'local' && ! empty(env('TURNSTILE_SECRET_KEY')),
    ],

    'stripe' => [
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

];
