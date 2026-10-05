<?php namespace Z1nex\Shop\Classes\Payments;

use Db;
use Carbon\Carbon;
use Z1nex\Shop\Models\Order;
use Z1nex\Shop\Models\PaymentEvent;

/**
 * Единственное место, где заказ меняет статус по результату оплаты.
 * Сюда приходят и вебхуки, и сверка по расписанию, поэтому повторный
 * вызов с тем же платежом обязан быть безопасным.
 */
class PaymentProcessor
{
    protected $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function start(Order $order, $returnUrl)
    {
        $payment = $this->gateway->create($order, $returnUrl);

        $order->payment_id = $payment['id'];
        $order->payment_url = $payment['confirmation_url'];
        $order->status = Order::STATUS_PENDING;
        $order->save();

        $this->log($order, 'checkout', 'payment.created', $payment['id'], PaymentEvent::RESULT_PENDING, 'Платёж создан, покупатель ушёл на страницу оплаты');

        return $payment['confirmation_url'];
    }

    /**
     * Тело уведомления не используется для решения: статус всегда
     * перечитывается из API платёжки по id из уведомления.
     */
    public function handle($paymentId, $source, array $payload = [], $ip = null)
    {
        $remote = $this->gateway->fetch($paymentId);

        if (!$remote) {
            return $this->log(null, $source, array_get($payload, 'event'), $paymentId, PaymentEvent::RESULT_REJECTED, 'Платёж не найден в платёжной системе', $payload, $ip);
        }

        return Db::transaction(function () use ($remote, $source, $payload, $ip) {
            $order = Order::where('payment_id', $remote['id'])->lockForUpdate()->first();

            if (!$order || (int) $order->id !== $remote['order_id']) {
                return $this->log(null, $source, array_get($payload, 'event'), $remote['id'], PaymentEvent::RESULT_REJECTED, 'Платёж не относится ни к одному заказу', $payload, $ip);
            }

            $event = 'payment.' . $remote['status'];

            if ($order->status === Order::STATUS_CANCELED && $remote['status'] === 'succeeded') {
                return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_REJECTED, 'Оплата пришла после отмены заказа, нужен возврат или ручное восстановление', $payload, $ip);
            }

            if (!$order->isPending()) {
                return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_DUPLICATE, "Заказ уже в статусе «{$order->status_label}», повтор проигнорирован", $payload, $ip);
            }

            if ($remote['status'] === 'succeeded') {
                if (abs($remote['amount'] - $order->total) > 0.009) {
                    return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_REJECTED, "Сумма платежа {$remote['amount']} не совпадает с суммой заказа {$order->total}", $payload, $ip);
                }

                $order->status = Order::STATUS_PAID;
                $order->paid_at = Carbon::now();
                $order->save();

                return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_APPLIED, 'Оплата подтверждена, заказ оплачен', $payload, $ip);
            }

            if ($remote['status'] === 'canceled') {
                $order->status = Order::STATUS_CANCELED;
                $order->save();

                return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_APPLIED, 'Платёж отменён, заказ отменён', $payload, $ip);
            }

            return $this->log($order, $source, $event, $remote['id'], PaymentEvent::RESULT_PENDING, "Платёж в статусе {$remote['status']}, ждём", $payload, $ip);
        });
    }

    public function expire(Order $order)
    {
        return Db::transaction(function () use ($order) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();
            if (!$order->isPending()) {
                return null;
            }

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            return $this->log($order, 'reconcile', 'order.expired', $order->payment_id, PaymentEvent::RESULT_APPLIED, 'Оплата не подтвердилась вовремя, заказ отменён');
        });
    }

    protected function log($order, $source, $event, $paymentId, $result, $message, array $payload = [], $ip = null)
    {
        return PaymentEvent::create([
            'order_id'   => $order ? $order->id : null,
            'source'     => $source,
            'event'      => $event ?: 'unknown',
            'payment_id' => $paymentId,
            'result'     => $result,
            'message'    => $message,
            'ip'         => $ip,
            'payload'    => $payload ?: null,
        ]);
    }
}
