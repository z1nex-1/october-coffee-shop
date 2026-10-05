<?php

return [
    'payment' => [
        // sandbox — встроенная имитация ЮKassa, yookassa — боевой API
        'driver' => env('SHOP_PAYMENT_DRIVER', 'sandbox'),
        'yookassa' => [
            'shop_id'    => env('YOOKASSA_SHOP_ID'),
            'secret_key' => env('YOOKASSA_SECRET_KEY'),
            // https://yookassa.ru/developers/using-api/webhooks#ip
            'ips' => [
                '185.71.76.0/27',
                '185.71.77.0/27',
                '77.75.153.0/25',
                '77.75.156.11',
                '77.75.156.35',
                '77.75.154.128/25',
                '2a02:5180::/32',
            ],
        ],
        // заказ без подтверждённой оплаты отменяется через столько минут
        'expire_minutes' => 60,
    ],

    'cdek' => [
        'url'            => env('CDEK_API_URL', 'https://api.edu.cdek.ru/v2'),
        'client_id'      => env('CDEK_CLIENT_ID'),
        'client_secret'  => env('CDEK_CLIENT_SECRET'),
        'from_city_code' => (int) env('CDEK_FROM_CITY_CODE', 44),
        // режимы: 3 — склад-дверь (курьер), 4 — склад-склад (ПВЗ)
        'modes'          => [3, 4],
        'timeout'        => 8,
    ],

    'package' => [
        'length' => 25,
        'width'  => 18,
        'height' => 10,
        'tare_grams' => 150,
    ],

    'free_delivery_from' => 5000,

    'demo' => [
        'nightly_reset' => env('SHOP_DEMO_RESET', false),
    ],
];
