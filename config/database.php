<?php

return [
    'fetch' => PDO::FETCH_CLASS,

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', 'storage/database.sqlite'),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'     => 'mysql',
            'engine'     => 'InnoDB',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'database'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset'    => 'utf8mb4',
            'collation'  => 'utf8mb4_unicode_ci',
            'prefix'     => '',
            'varcharmax' => 191,
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'database'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'database'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'prefix'   => '',
        ],
    ],

    'migrations' => 'migrations',

    'redis' => [
        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],

    'useConfigForTesting' => env('DB_USE_CONFIG_FOR_TESTING', false),
];
