<?php namespace Z1nex\Shop\Tests;

use PluginTestCase;
use Carbon\Carbon;
use Z1nex\Shop\Classes\Payments\Gateway;
use Z1nex\Shop\Classes\Payments\PaymentProcessor;
use Z1nex\Shop\Models\Order;
use Z1nex\Shop\Models\PaymentEvent;

class FakeGateway implements Gateway
{
    public $payments = [];

    public function create(Order $order, $returnUrl)
    {
        $id = 'fake-' . $order->id;
        $this->payments[$id] = ['id' => $id, 'status' => 'pending', 'amount' => $order->total, 'order_id' => $order->id];
        return ['id' => $id, 'status' => 'pending', 'confirmation_url' => 'https://pay.test/' . $id];
    }

    public function fetch($paymentId)
    {
        return array_get($this->payments, $paymentId);
    }

    public function name()
    {
        return 'fake';
    }
}

class PaymentProcessorTest extends PluginTestCase
{
    protected $gateway;
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new FakeGateway;
        $this->processor = new PaymentProcessor($this->gateway);
    }

    protected function startOrder($total = 1500)
    {
        $order = new Order([
            'customer_name'   => 'Тест',
            'phone'           => '+79990000000',
            'delivery_method' => 'pickup',
        ]);
        $order->total = $total;
        $order->save();

        $this->processor->start($order, 'https://shop.test/order');

        return $order->reload();
    }

    public function testSucceededWebhookPaysOrder()
    {
        $order = $this->startOrder();
        $this->gateway->payments[$order->payment_id]['status'] = 'succeeded';

        $event = $this->processor->handle($order->payment_id, 'webhook', ['event' => 'payment.succeeded']);

        $this->assertEquals(PaymentEvent::RESULT_APPLIED, $event->result);
        $this->assertEquals(Order::STATUS_PAID, $order->reload()->status);
        $this->assertNotNull($order->paid_at);
    }

    public function testRepeatedWebhookDoesNotChangeOrder()
    {
        $order = $this->startOrder();
        $this->gateway->payments[$order->payment_id]['status'] = 'succeeded';

        $this->processor->handle($order->payment_id, 'webhook');
        $paidAt = $order->reload()->paid_at;

        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $event = $this->processor->handle($order->payment_id, 'webhook');
        Carbon::setTestNow();

        $this->assertEquals(PaymentEvent::RESULT_DUPLICATE, $event->result);
        $this->assertEquals($paidAt, $order->reload()->paid_at);
    }

    public function testWebhookBodyIsNotTrusted()
    {
        $order = $this->startOrder();

        $event = $this->processor->handle($order->payment_id, 'webhook', ['event' => 'payment.succeeded', 'object' => ['status' => 'succeeded']]);

        $this->assertEquals(PaymentEvent::RESULT_PENDING, $event->result);
        $this->assertEquals(Order::STATUS_PENDING, $order->reload()->status);
    }

    public function testAmountMismatchIsRejected()
    {
        $order = $this->startOrder(1500);
        $this->gateway->payments[$order->payment_id]['status'] = 'succeeded';
        $this->gateway->payments[$order->payment_id]['amount'] = 15;

        $event = $this->processor->handle($order->payment_id, 'webhook');

        $this->assertEquals(PaymentEvent::RESULT_REJECTED, $event->result);
        $this->assertEquals(Order::STATUS_PENDING, $order->reload()->status);
    }

    public function testUnknownPaymentIsRejected()
    {
        $event = $this->processor->handle('does-not-exist', 'webhook');

        $this->assertEquals(PaymentEvent::RESULT_REJECTED, $event->result);
        $this->assertNull($event->order_id);
    }

    public function testPaymentAfterCancelIsNotApplied()
    {
        $order = $this->startOrder();
        $this->processor->expire($order);
        $this->gateway->payments[$order->payment_id]['status'] = 'succeeded';

        $event = $this->processor->handle($order->payment_id, 'reconcile');

        $this->assertEquals(PaymentEvent::RESULT_REJECTED, $event->result);
        $this->assertEquals(Order::STATUS_CANCELED, $order->reload()->status);
    }
}
