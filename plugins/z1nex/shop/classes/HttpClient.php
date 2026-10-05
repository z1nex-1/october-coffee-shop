<?php namespace Z1nex\Shop\Classes;

use RuntimeException;

class HttpClient
{
    protected $timeout;

    public function __construct($timeout = 10)
    {
        $this->timeout = $timeout;
    }

    public function request($method, $url, array $options = [])
    {
        $headers = array_get($options, 'headers', []);
        $body = null;

        if (array_key_exists('json', $options)) {
            $body = json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            $headers['Content-Type'] = 'application/json';
        }
        elseif (array_key_exists('form', $options)) {
            $body = http_build_query($options['form']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if (!empty($options['query'])) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options['query']);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_HTTPHEADER     => array_map(function ($name, $value) {
                return $name . ': ' . $value;
            }, array_keys($headers), $headers),
        ]);

        if (!empty($options['basic'])) {
            curl_setopt($ch, CURLOPT_USERPWD, implode(':', $options['basic']));
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("{$method} {$url}: {$error}");
        }

        $data = json_decode($response, true);

        if ($status >= 400) {
            $message = array_get($data, 'description') ?: array_get($data, 'errors.0.message') ?: substr($response, 0, 200);
            throw new RuntimeException("{$method} {$url}: HTTP {$status}, {$message}", $status);
        }

        return $data;
    }
}
