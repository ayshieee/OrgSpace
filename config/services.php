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

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'libreoffice' => [
        // Differs between this dev box and wherever this app is actually
        // hosted later — never hardcode the Windows path as a fallback used
        // in production. `?:` (not the env() default arg) because an empty
        // string left in .env is a real value to env() — it wouldn't trigger
        // the default, silently invoking soffice as `""`.
        'binary' => env('LIBREOFFICE_BINARY') ?: (PHP_OS_FAMILY === 'Windows'
            ? 'C:\Program Files\LibreOffice\program\soffice.exe'
            : 'soffice'),
        'timeout' => env('LIBREOFFICE_TIMEOUT') ?: 60,
    ],

];
