<?php namespace Z1nex\Shop\Classes\Payments;

use RuntimeException;
use Z1nex\Shop\Classes\HttpClient;
use Z1nex\Shop\Models\Order;

class YooKassaGateway implements Gateway
{
    const API = 'https://api.yookassa.ru/v3';

    protected $shopId;
    protected $secretKey;
    protected $http;

    public function __construct($shopId, $secretKey, HttpClient $http = null)
    {
        if (!$shopId || !$secretKey) {
            throw new RuntimeException('Для ЮKassa нужны YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY');
        }
        $this->shopId = $shopId;
        $this->secretKey = $secretKey;
        $this->http = $http ?: new HttpClient(15);
    }

    public function name()
    {
        return 'yookassa';
    }

    public function create(Order $order, $returnUrl)
    {
        $payment = $this->http->request('POST', self::API . '/payments', [
            'basic'   => [$this->shopId, $this->secretKey],
            // повтор с тем же ключом вернёт уже созданный платёж, а не второй
            'headers' => ['Idempotence-Key' => $order->idempotenceKey()],
            'json'    => [
                'amount'       => ['value' => number_format($order->total, 2, '.', ''), 'currency' => 'RUB'],
                'capture'      => true,
                'confirmation' => ['type' => 'redirect', 'return_url' => $returnUrl],
                'description'  => "Заказ {$order->number}",
                'metadata'     => ['order_id' => $order->id],
            ],
        ]);

        return [
            'id'               => $payment['id'],
            'status'           => $payment['status'],
            'confirmation_url' => array_get($payment, 'confirmation.confirmation_url'),
        ];
    }

    public function fetch($paymentId)
    {
        try {
            $payment = $this->http->request('GET', self::API . '/payments/' . rawurlencode($paymentId), [
                'basic' => [$this->shopId, $this->secretKey],
            ]);
        }
        catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }

        return [
            'id'       => $payment['id'],
            'status'   => $payment['status'],
            'amount'   => (float) array_get($payment, 'amount.value'),
            'order_id' => (int) array_get($payment, 'metadata.order_id'),
        ];
    }
}
