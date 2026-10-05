<?php

return [
    'driver' => env('MAIL_DRIVER', 'smtp'),

    'host' => env('MAIL_HOST', 'smtp.mailgun.org'),

    'port' => env('MAIL_PORT', 587),

    'from' => ['address' => 'noreply@domain.tld', 'name' => 'OctoberCMS'],

    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    'username' => env('MAIL_USERNAME', null),

    'password' => env('MAIL_PASSWORD', null),

    'sendmail' => '/usr/sbin/sendmail -bs',
];
