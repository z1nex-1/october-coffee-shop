<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => 120,

    'expire_on_close' => false,

    'encrypt' => false,

    'files' => storage_path('framework/sessions'),

    'connection' => null,

    'table' => 'sessions',

    'lottery' => [2, 100],

    'cookie' => 'october_session',

    'path' => '/',

    'domain' => null,

    'http_only' => true,

    'secure' => false,

    'same_site' => null,
];
