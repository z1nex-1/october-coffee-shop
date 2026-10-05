<?php

return [
    'debug' => env('APP_DEBUG', true),

    'name' => 'October CMS',

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'Europe/Moscow',

    'locale' => 'ru',

    'fallback_locale' => 'en',

    'key' => env('APP_KEY', ''),

    'cipher' => 'AES-256-CBC',

    'log' => 'single',

    'providers' => array_merge(include(base_path('modules/system/providers.php')), [
        'System\ServiceProvider',
    ]),

    'aliases' => array_merge(include(base_path('modules/system/aliases.php')), [
    ]),
];
