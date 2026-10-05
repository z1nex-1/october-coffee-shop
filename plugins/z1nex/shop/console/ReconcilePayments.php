<?php namespace Z1nex\Shop\Console;

use Config;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Z1nex\Shop\Classes\Payments\PaymentProcessor;
use Z1nex\Shop\Models\Order;

/**
 * Страховка от потерянных вебхуков: проходит по заказам, которые
 * ждут оплаты, и спрашивает статус у платёжной системы напрямую.
 */
class ReconcilePayments extends Command
{
    protected $name = 'shop:reconcile-payments';

    protected $description = 'Сверить неоплаченные заказы с платёжной системой';

    public function handle()
    {
        $processor = app(PaymentProcessor::class);
        $deadline = Carbon::now()->subMinutes(Config::get('z1nex.shop::payment.expire_minutes'));

        $orders = Order::where('status', Order::STATUS_PENDING)->whereNotNull('payment_id')->get();

        foreach ($orders as $order) {
            $event = $processor->handle($order->payment_id, 'reconcile');
            $order->reload();

            if ($order->isPending() && $order->created_at->lt($deadline)) {
                $processor->expire($order);
                $this->line("{$order->number}: отменён по таймауту");
                continue;
            }

            $this->line("{$order->number}: {$event->result_label}");
        }

        $this->info('Проверено заказов: ' . $orders->count());
    }
}
