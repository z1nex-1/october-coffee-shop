<?php

return [
    'default' => 'local',

    'cloud' => 's3',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'url'    => '/storage/app',
        ],

        's3' => [
            'driver' => 's3',
            'key'    => 'your-key',
            'secret' => 'your-secret',
            'region' => 'your-region',
            'bucket' => 'your-bucket',
        ],

        'rackspace' => [
            'driver'    => 'rackspace',
            'username'  => 'your-username',
            'key'       => 'your-key',
            'container' => 'your-container',
            'endpoint'  => 'https://identity.api.rackspacecloud.com/v2.0/',
            'region'    => 'IAD',
        ],
    ],
];
