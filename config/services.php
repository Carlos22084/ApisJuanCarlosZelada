<?php

return [

    'cloudflare_ai' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'token' => env('CLOUDFLARE_API_TOKEN'),
        'model' => env('CLOUDFLARE_AI_MODEL', '@cf/black-forest-labs/flux-2-klein-4b'),
        'width' => env('CLOUDFLARE_AI_WIDTH', 1024),
        'height' => env('CLOUDFLARE_AI_HEIGHT', 1024),
    ],

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

    'resend' => [
        'key' => env('RESEND_API_KEY', env('RESEND_KEY')),
        'notifications_enabled' => env('RESEND_NOTIFICATIONS_ENABLED', true),
    ],

];
