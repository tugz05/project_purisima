<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverb Server Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file allows you to configure the Reverb server
    | settings. You can configure the host, port, and other options
    | for your Reverb server instance.
    |
    */

    'host' => env('REVERB_HOST', '127.0.0.1'),
    'port' => env('REVERB_PORT', 8080),
    'hostname' => env('REVERB_HOSTNAME', '127.0.0.1'),
    'options' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb App Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows you to configure the Reverb app settings
    | such as the app ID, key, and secret for authentication.
    |
    */

    'app_id' => env('REVERB_APP_ID'),
    'app_key' => env('REVERB_APP_KEY'),
    'app_secret' => env('REVERB_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Reverb SSL Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows you to configure SSL settings for Reverb
    | when running in production environments.
    |
    */

    'ssl' => [
        'local_cert' => env('REVERB_SSL_LOCAL_CERT'),
        'local_pk' => env('REVERB_SSL_LOCAL_PK'),
        'passphrase' => env('REVERB_SSL_PASSPHRASE'),
        'verify_peer' => env('REVERB_SSL_VERIFY_PEER', false),
    ],

];
