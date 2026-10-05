<?php namespace Z1nex\Shop\Classes\Payments;

use Url;
use Z1nex\Shop\Models\Order;
use Z1nex\Shop\Models\SandboxPayment;

/**
 * Имитация ЮKassa для демо-стенда: тот же жизненный цикл платежа
 * и тот же формат уведомлений, только без реальных денег.
 */
class SandboxGateway implements Gateway
{
    public function name()
    {
        return 'sandbox';
    }

    public function create(Order $order, $returnUrl)
    {
        $key = $order->idempotenceKey();

        $payment = SandboxPayment::where('idempotence_key', $key)->first() ?: SandboxPayment::create([
            'id'              => self::uuid(),
            'status'          => 'pending',
            'amount'          => $order->total,
            'description'     => "Заказ {$order->number}",
            'metadata'        => ['order_id' => $order->id],
            'return_url'      => $returnUrl,
            'idempotence_key' => $key,
        ]);

        return [
            'id'               => $payment->id,
            'status'           => $payment->status,
            'confirmation_url' => Url::to('sandbox/yookassa/' . $payment->id),
        ];
    }

    public function fetch($paymentId)
    {
        $payment = SandboxPayment::find($paymentId);
        if (!$payment) {
            return null;
        }

        return [
            'id'       => $payment->id,
            'status'   => $payment->status,
            'amount'   => (float) $payment->amount,
            'order_id' => (int) array_get($payment->metadata, 'order_id'),
        ];
    }

    public static function notification(SandboxPayment $payment)
    {
        return [
            'type'   => 'notification',
            'event'  => 'payment.' . $payment->status,
            'object' => [
                'id'         => $payment->id,
                'status'     => $payment->status,
                'paid'       => $payment->status === 'succeeded',
                'amount'     => ['value' => number_format($payment->amount, 2, '.', ''), 'currency' => 'RUB'],
                'created_at' => $payment->created_at->toIso8601String(),
                'metadata'   => $payment->metadata,
            ],
        ];
    }

    protected static function uuid()
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
