<?php

declare(strict_types=1);

/*
 * This file is part of Laravel GitHub.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    'default' => 'issues',

    /*
    |--------------------------------------------------------------------------
    | GitHub Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like. Note that the 5 supported authentication methods are:
    | "application", "jwt", "none", "private", and "token".
    |
    */

    'connections' => [

        'main' => [
            'method' => 'token',
            'token' => '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',
            // 'backoff'    => false,
            // 'cache'      => false,
            // 'version'    => 'v3',
            // 'enterprise' => false,
        ],

        'issues' => [
            'method' => 'private',
            'appId' => '252350',
            'keyPath' => storage_path('protected/perfect-wedding.2022-10-24.private-key.pem'),
            'installation_id' => 30538409,
        ],

        'app' => [
            'method' => 'application',
            'clientId' => 'your-client-id',
            'clientSecret' => 'your-client-secret',
            // 'backoff'      => false,
            // 'cache'        => false,
            // 'version'      => 'v3',
            // 'enterprise'   => false,
        ],

        'jwt' => [
            'method' => 'jwt',
            'token' => 'your-jwt-token',
            // 'backoff'      => false,
            // 'cache'        => false,
            // 'version'      => 'v3',
            // 'enterprise'   => false,
        ],

        'private' => [
            'method' => 'private',
            'appId' => 'your-github-app-id',
            'keyPath' => 'your-private-key-path',
            // 'key'        => 'your-private-key-content',
            // 'passphrase' => 'your-private-key-passphrase'
            // 'backoff'    => false,
            // 'cache'      => false,
            // 'version'    => 'v3',
            // 'enterprise' => false,
        ],

        'none' => [
            'method' => 'none',
            // 'backoff'    => false,
            // 'cache'      => false,
            // 'version'    => 'v3',
            // 'enterprise' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Cache
    |--------------------------------------------------------------------------
    |
    | Here are each of the cache configurations setup for your application.
    | Only the "illuminate" driver is provided out of the box. Example
    | configuration has been included.
    |
    */

    'cache' => [

        'main' => [
            'driver' => 'illuminate',
            'connector' => null, // null means use default driver
            // 'min'       => 43200,
            // 'max'       => 172800
        ],

        'bar' => [
            'driver' => 'illuminate',
            'connector' => 'redis', // config/cache.php
            // 'min'       => 43200,
            // 'max'       => 172800
        ],

    ],

];
