<?php

use Illuminate\Support\Facades\Facade;

return [

    'name'              => env('APP_NAME', 'LanOps'),
    'tagline'           => env('APP_TAGLINE', 'Lans in South Yorkshire!'),
    'env'               => env('APP_ENV', 'production'),
    'debug'             => env('APP_DEBUG', false),
    'url'               => env('APP_URL', 'localhost'),
    'timezone'          => 'Europe/London',
    'locale'            => 'en',
    'fallback_locale'   => 'en',
    'key'               => env('APP_KEY'),
    'cipher'            => 'AES-256-CBC',
    'log'               => env('APP_LOG', 'errorlog'),

    'logo'              => '/images/logo_main.png',
    'favicon'           => '/images/favicon.ico',
    'basket_name'       => str_replace(" ", "_", strtolower(env('APP_NAME', 'LanOps'))) . '-basket',

    'discord_link'      => env('DISCORD_LINK', ""),
    'discord_id'        => env('DISCORD_ID', ""),

    'facebook_link'     => env('FACEBOOK_LINK', ''),

    'steam_link'        => env('STEAM_LINK', ''),

    'youtube_link'       => env('YOUTUBE_LINK', ''),

    'bsky_link'         => env('BSKY_LINK'),

    'currency'          => env('APP_CURRENCY', 'GBP'),
    'currency_symbol'   => env('APP_CURRENCY', '£'),

    'discord_bot_url'   => env('DISCORD_BOT_URL', ''),
    'discord_bot_user' => env('DISCORD_BOT_USER', ''),
    'discord_bot_pass' => env('DISCORD_BOT_PASS', ''),
    'discord_bot_secret' => env('DISCORD_BOT_SECRET'),
    'discord_auth_url' => env('DISCORD_AUTH_URL', ''),
    'discord_redirect_url' => env('DISCORD_REDIRECT_URL', ''),
    'discord_client_id' => env('DISCORD_CLIENT_ID', ''),
    'discord_client_secret' => env('DISCORD_CLIENT_SECRET', ''),
    'discord_scope' => env('DISCORD_SCOPE', 'identify'),

    'refer_a_friend_discount' => env('REFER_A_FRIEND_DISCOUNT', 10),

    'seo_description' => env('SEO_DESCRIPTION', ''),
    'seo_keywords' => env('SEO_KEYWORDS', 'UK Lan, LAN, LAN Party, South Yorkshire, Gaming, lanops, UK, community'),
    
    'google_tag_id' => env('GOOGLE_TAG_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | Service providers are the framework defaults, plus bootstrap/providers.php
    | and package auto-discovery.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Helpers' => App\Libraries\Helpers::class,
    ])->toArray(),

];
