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

    'upc' => [
        'key' => env('UPC_KEY')
    ],
    
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'product_id' => env('STRIPE_PRODUCT_ID')
    ],

    'cardconnect' => [
        'gateway_url' => env('CARDCONNECT_GATEWAY_URL'),
        'bolt_api_url' => env('CARDCONNECT_BOLT_API_URL'),
        'bolt_gateway_url' => env('CARDCONNECT_BOLT_GATEWAY_URL'),
        'bolt_api_key' => env('CARDCONNECT_BOLT_API_KEY'),
        'mid' => env('CARDCONNECT_MID'),
        'username' => env('CARDCONNECT_USERNAME'),
        'password' => env('CARDCONNECT_PASSWORD'),
        'base64Auth' => 'Basic ' . base64_encode(env('CARDCONNECT_USERNAME') . ':' . env('CARDCONNECT_PASSWORD'))
    ],
    
    'qztray' => [
        'key' => env('QZTRAY_KEY'),
        'cert' => env('QZTRAY_CERT')
    ],

    'intuit' => [
        'quickbooks' => [
            'client_id' => env('QUICKBOOKS_CLIENT_ID'),
            'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
            'redirect_url' => env('QUICKBOOKS_REDIRECT_URL'),
            'env' => env('QUICKBOOKS_ENV')
        ]
    ],

];
