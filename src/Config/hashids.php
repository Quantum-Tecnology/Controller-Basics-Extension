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
    | Transform Attributes on Model
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the attributes you wish to transform on
    | your models. Of course, you may use many attributes at once using the
    | manager class.
    | Example: 'attributes' => ['id', 'user_id']
    |
    */

    'attributes' => [],

    /*
    |--------------------------------------------------------------------------
    | Regex Attributes to Transform on Request
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the attributes you wish to transform on
    | your requests. Of course, you may use many attributes at once using the
    | manager class.
    |
    */
    'regex' => env('HASHID_REGEX', '/id$|_id$|Id$|_ids$/'), // Default

    /*
    |--------------------------------------------------------------------------
    | Headers to Transform on Request
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the headers you wish to transform on
    | your requests. Of course, you may use many headers at once using the
    | manager class.
    |
    */
    'headers' => [
        'regex' => env('HASHID_HEADER_REGEX', '/^(X-Admin|X-Agent|X-User)/i'),
    ],

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
            'salt'     => 'example',
            'length'   => 0,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
    ],
];
