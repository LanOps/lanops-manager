<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_SECRET'),
    ],

    'ses' => [
        'key'    => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'stripe' => [
        'model'  => App\User::class,
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'facebook' => [
        'client_id' =>  '',
        'client_secret' => '',
        'redirect' => env('FACEBOOK_REDIRECT'),
    ],
    'steam' => [
        'client_id'     => null,
        'client_secret' => env('STEAM_API_KEY'),
        // Set at runtime in SteamController so the return URL uses the right scheme
        'redirect'      => null,
        'force_https'   => env('STEAM_ENABLE_HTTPS', true),
    ],
    'igdb' => [
        'key' => env('IGDB_API_KEY'),
        'url' => env('IGDB_API_URL'),
    ]

];
