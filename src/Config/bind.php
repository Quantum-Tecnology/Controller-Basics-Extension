<?php

declare(strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Bind
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'enabled' => env('ENABLED_BIND', false),

    /*
    |--------------------------------------------------------------------------
    | Transform Attributes on Model
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the attributes you wish to transform on
    | your models. Of course, you may use many attributes at once using the
    | manager class.
    | Example: 'attributes' => ['pessoas', 'people]
    |
    */

    'attributes' => [
    ],
];
