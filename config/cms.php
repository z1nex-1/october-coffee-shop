<?php

return [
    'activeTheme' => 'coffee',

    'edgeUpdates' => false,

    'backendUri' => 'backend',

    'backendForceSecure' => false,

    'backendForceRemember' => true,

    'backendTimezone' => 'UTC',

    'backendSkin' => 'Backend\Skins\Standard',

    'runMigrationsOnLogin' => null,

    'loadModules' => ['System', 'Backend', 'Cms'],

    'disableCoreUpdates' => true,

    'disablePlugins' => [],

    'enableRoutesCache' => env('ROUTES_CACHE', false),

    'urlCacheTtl' => 10,

    'parsedPageCacheTTL' => 10,

    'enableAssetCache' => env('ASSET_CACHE', false),

    'enableAssetMinify' => null,

    'enableAssetDeepHashing' => null,

    'databaseTemplates' => env('DATABASE_TEMPLATES', false),

    'pluginsPath' => '/plugins',

    'themesPath' => '/themes',

    'storage' => [
        'uploads' => [
            'disk'            => 'local',
            'folder'          => 'uploads',
            'path'            => '/storage/app/uploads',
            'temporaryUrlTTL' => 3600,
        ],

        'media' => [
            'disk'   => 'local',
            'folder' => 'media',
            'path'   => '/storage/app/media',
        ],
    ],

    'convertLineEndings' => false,

    'linkPolicy' => env('LINK_POLICY', 'detect'),

    'defaultMask' => ['file' => null, 'folder' => null],

    'enableSafeMode' => env('CMS_SAFE_MODE', null),

    'enableCsrfProtection' => env('ENABLE_CSRF', true),

    'forceBytecodeInvalidation' => true,

    'enableTwigStrictVariables' => false,

    'restrictBaseDir' => true,

    'enableBackendServiceWorkers' => false,
];
