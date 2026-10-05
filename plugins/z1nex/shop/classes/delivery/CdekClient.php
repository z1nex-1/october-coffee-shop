<?php namespace Z1nex\Shop\Classes\Delivery;

use Cache;
use Log;
use RuntimeException;
use Z1nex\Shop\Classes\HttpClient;

class CdekClient
{
    const MODE_COURIER = 3;
    const MODE_PVZ = 4;

    protected $config;
    protected $http;

    /**
     * Запасная сетка на случай, когда API СДЭК не отвечает:
     * оформление заказа не должно падать из-за чужого сервиса.
     */
    protected static $fallbackCities = [
        44  => 'Москва',
        137 => 'Санкт-Петербург',
        424 => 'Казань',
        250 => 'Екатеринбург',
        270 => 'Новосибирск',
        414 => 'Нижний Новгород',
        438 => 'Ростов-на-Дону',
        435 => 'Краснодар',
    ];

    public function __construct(array $config, HttpClient $http = null)
    {
        $this->config = $config;
        $this->http = $http ?: new HttpClient(array_get($config, 'timeout', 8));
    }

    public function suggestCities($query)
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        try {
            $rows = $this->get('/location/suggest/cities', ['name' => $query, 'country_code' => 'RU']);
            return array_map(function ($row) {
                return ['code' => (int) $row['code'], 'name' => $row['full_name']];
            }, array_slice((array) $rows, 0, 8));
        }
        catch (RuntimeException $e) {
            $this->report($e);
            return collect(self::$fallbackCities)
                ->filter(function ($name) use ($query) {
                    return mb_stripos($name, $query) === 0;
                })
                ->map(function ($name, $code) {
                    return ['code' => $code, 'name' => $name];
                })
                ->values()
                ->all();
        }
    }

    /**
     * Тарифы, сгруппированные по способу доставки: courier и pvz,
     * в каждой группе не больше трёх самых дешёвых.
     */
    public function tariffs($cityCode, $weightGrams, array $package)
    {
        $cacheKey = 'z1nex.shop.cdek.tariffs.' . md5(json_encode([$cityCode, $weightGrams, $package]));

        return Cache::remember($cacheKey, 30, function () use ($cityCode, $weightGrams, $package) {
            try {
                $result = $this->post('/calculator/tarifflist', [
                    'from_location' => ['code' => (int) $this->config['from_city_code']],
                    'to_location'   => ['code' => (int) $cityCode],
                    'packages'      => [[
                        'weight' => (int) $weightGrams,
                        'length' => (int) $package['length'],
                        'width'  => (int) $package['width'],
                        'height' => (int) $package['height'],
                    ]],
                ]);

                $tariffs = array_map(function ($row) {
                    return [
                        'code'     => (int) $row['tariff_code'],
                        'name'     => $row['tariff_name'],
                        'mode'     => (int) $row['delivery_mode'],
                        'price'    => (int) ceil($row['delivery_sum']),
                        'days_min' => (int) $row['period_min'],
                        'days_max' => (int) $row['period_max'],
                    ];
                }, array_get($result, 'tariff_codes', []));

                return $this->group($tariffs, false);
            }
            catch (RuntimeException $e) {
                $this->report($e);
                return $this->group($this->fallbackTariffs($cityCode, $weightGrams), true);
            }
        });
    }

    public function deliveryPoints($cityCode)
    {
        try {
            $rows = $this->get('/deliverypoints', ['city_code' => (int) $cityCode, 'type' => 'PVZ']);
            return array_map(function ($row) {
                return [
                    'code'      => $row['code'],
                    'address'   => array_get($row, 'location.address'),
                    'work_time' => array_get($row, 'work_time'),
                ];
            }, array_slice((array) $rows, 0, 40));
        }
        catch (RuntimeException $e) {
            $this->report($e);
            return [];
        }
    }

    protected function group(array $tariffs, $estimate)
    {
        $groups = ['courier' => [], 'pvz' => []];

        foreach ($tariffs as $tariff) {
            if ($tariff['mode'] === self::MODE_COURIER) {
                $groups['courier'][] = $tariff + ['estimate' => $estimate];
            }
            elseif ($tariff['mode'] === self::MODE_PVZ) {
                $groups['pvz'][] = $tariff + ['estimate' => $estimate];
            }
        }

        foreach ($groups as &$group) {
            usort($group, function ($a, $b) { return $a['price'] - $b['price']; });
            $group = array_slice($group, 0, 3);
        }

        return $groups;
    }

    protected function fallbackTariffs($cityCode, $weightGrams)
    {
        $local = (int) $cityCode === (int) $this->config['from_city_code'];
        $kg = max(1, (int) ceil($weightGrams / 1000));
        $base = $local ? 190 : 290;

        return [
            ['code' => 136, 'name' => 'Посылка склад-склад', 'mode' => self::MODE_PVZ, 'price' => $base + 60 * $kg, 'days_min' => $local ? 1 : 3, 'days_max' => $local ? 2 : 7],
            ['code' => 137, 'name' => 'Посылка склад-дверь', 'mode' => self::MODE_COURIER, 'price' => $base + 150 + 60 * $kg, 'days_min' => $local ? 1 : 3, 'days_max' => $local ? 2 : 7],
        ];
    }

    protected function get($path, array $query)
    {
        return $this->http->request('GET', $this->config['url'] . $path, [
            'headers' => ['Authorization' => 'Bearer ' . $this->token()],
            'query'   => $query,
        ]);
    }

    protected function post($path, array $json)
    {
        return $this->http->request('POST', $this->config['url'] . $path, [
            'headers' => ['Authorization' => 'Bearer ' . $this->token()],
            'json'    => $json,
        ]);
    }

    protected function token()
    {
        $key = 'z1nex.shop.cdek.token.' . md5($this->config['client_id']);

        if ($token = Cache::get($key)) {
            return $token;
        }

        if (!$this->config['client_id'] || !$this->config['client_secret']) {
            throw new RuntimeException('Не заданы CDEK_CLIENT_ID и CDEK_CLIENT_SECRET');
        }

        $result = $this->http->request('POST', $this->config['url'] . '/oauth/token', [
            'form' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
            ],
        ]);

        // минута запаса, чтобы токен не истёк между проверкой и запросом
        $minutes = max(1, (int) floor((array_get($result, 'expires_in', 3600) - 60) / 60));
        Cache::put($key, $result['access_token'], $minutes);

        return $result['access_token'];
    }

    protected function report(RuntimeException $e)
    {
        Log::warning('CDEK API: ' . $e->getMessage());
    }
}
