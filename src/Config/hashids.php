<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'default' => env('HASHID_DEFAULT', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Enable Cryptography
    |--------------------------------------------------------------------------
    |
    */

    'enable_cryptography' => env('ENABLE_CRYPTOGRAPHY', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Unit Status Checker
    |--------------------------------------------------------------------------
    |
    */

    'enable_unit_status_checker' => env('ENABLE_UNIT_STATUS_CHECKER', false),

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */

    'connections' => [
        'main' => [
            'salt'   => '',
            'length' => 0,
            // 'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        ],
    ],
];
