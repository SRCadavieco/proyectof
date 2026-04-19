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

    'gemini' => [
        'url' => env('GEMINI_BACKEND_URL'),
        'token' => env('GEMINI_BACKEND_TOKEN'),
        'path' => env('GEMINI_BACKEND_PATH', '/generate-design'),
        'auth_header' => env('GEMINI_BACKEND_AUTH_HEADER', 'bearer'),
        'auth_query_key' => env('GEMINI_BACKEND_AUTH_QUERY_KEY'),
        'method' => env('GEMINI_BACKEND_METHOD', 'POST'),
    ],

    'nanobanana' => [
        'url' => env('NANOBANANA_API_URL', 'https://fabricai-278322460825.europe-west1.run.app'),
        'key' => env('NANOBANANA_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'pixazo' => [
        'key' => env('PIXAZO_API_KEY'),
    ],

    'chutes' => [
        'token' => env('CHUTES_API_TOKEN'),
        'path'  => '/generate',
        'urls'  => [
            'z_image_turbo' => env('CHUTES_URL_ZIMAGE',  'https://chutes-z-image-turbo.chutes.ai'),
            'flux_schnell'  => env('CHUTES_URL_FLUX',    'https://chutes-flux-1-schnell.chutes.ai'),
        ],
    ],

    'stripe' => [
        'prices' => [
            'pro_monthly' => env('STRIPE_PRO_MONTHLY_PRICE'),
            'pro_yearly' => env('STRIPE_PRO_YEARLY_PRICE'),
            'studio_monthly' => env('STRIPE_STUDIO_MONTHLY_PRICE'),
            'studio_yearly' => env('STRIPE_STUDIO_YEARLY_PRICE'),
        ],
        'portal_configuration' => env('STRIPE_PORTAL_CONFIGURATION'),
    ],

];
