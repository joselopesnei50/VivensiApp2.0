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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'asaas' => [
        'api_key' => env('ASAAS_API_KEY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
        'environment' => env('ASAAS_ENVIRONMENT', 'sandbox'),
    ],

    'pagseguro' => [
        'email'          => env('PAGSEGURO_EMAIL'),
        'token'          => env('PAGSEGURO_TOKEN'),
        'webhook_token'  => env('PAGSEGURO_WEBHOOK_TOKEN'),
        'environment'    => env('PAGSEGURO_ENV', 'sandbox'),
    ],

    'abacatepay' => [
        'api_key'        => env('ABACATEPAY_API_KEY'),
        'webhook_secret' => env('ABACATEPAY_WEBHOOK_SECRET'),
        'hmac_key'       => env('ABACATEPAY_HMAC_KEY'),
        'environment'    => env('ABACATEPAY_ENV', 'sandbox'), // sandbox ou production
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
    ],

    'whatsapp' => [
        'bot_secret' => env('WHATSAPP_BOT_SECRET'),
    ],

];
