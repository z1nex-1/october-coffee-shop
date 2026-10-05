<?php namespace Z1nex\Shop\Components;

use Config;
use Url;
use Cms\Classes\ComponentBase;
use Z1nex\Shop\Classes\Payments\PaymentProcessor;
use Z1nex\Shop\Models\Order;

class OrderStatus extends ComponentBase
{
    public $order;
    public $sandbox;
    public $notificationUrl;
    public $webhookUrl;

    public function componentDetails()
    {
        return [
            'name'        => 'Статус заказа',
            'description' => 'Страница, куда покупатель возвращается после оплаты',
        ];
    }

    public function defineProperties()
    {
        return [
            'hash' => [
                'title'   => 'Код заказа',
                'type'    => 'string',
                'default' => '{{ :hash }}',
            ],
        ];
    }

    public function onRun()
    {
        if (!$this->load()) {
            return $this->controller->run('404');
        }

        // вебхук может прийти позже, чем покупатель вернётся на сайт
        if ($this->order->isPending() && $this->order->payment_id) {
            app(PaymentProcessor::class)->handle($this->order->payment_id, 'return');
            $this->order->reload();
        }
    }

    public function onRefresh()
    {
        if (!$this->load()) {
            return;
        }

        return ['#order-status' => $this->renderPartial('@status')];
    }

    protected function load()
    {
        $hash = $this->property('hash') ?: post('hash');
        if (!$hash || !preg_match('/^[A-Za-z0-9]{24}$/', $hash)) {
            return false;
        }

        $this->order = Order::with(['items', 'events'])->where('hash', $hash)->first();
        if (!$this->order) {
            return false;
        }

        $this->sandbox = Config::get('z1nex.shop::payment.driver') === 'sandbox';
        if ($this->sandbox && $this->order->payment_id) {
            $this->notificationUrl = Url::to('sandbox/yookassa/' . $this->order->payment_id . '/notification');
            $this->webhookUrl = Url::to('shop/webhook/yookassa');
        }

        return true;
    }
}
