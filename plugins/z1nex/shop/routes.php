<?php

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Z1nex\Shop\Classes\Payments\PaymentProcessor;
use Z1nex\Shop\Classes\Payments\SandboxGateway;
use Z1nex\Shop\Models\PaymentEvent;
use Z1nex\Shop\Models\SandboxPayment;

Route::post('shop/webhook/yookassa', function (Request $request) {
    $payload = $request->json()->all();
    $paymentId = array_get($payload, 'object.id');

    // ЮKassa не подписывает уведомления, поэтому в боевом режиме
    // принимаем их только с её адресов, а статус всё равно перечитываем через API
    if (Config::get('z1nex.shop::payment.driver') === 'yookassa'
        && !IpUtils::checkIp($request->ip(), Config::get('z1nex.shop::payment.yookassa.ips'))) {
        PaymentEvent::create([
            'source' => 'webhook', 'event' => array_get($payload, 'event', 'unknown'), 'payment_id' => $paymentId,
            'result' => PaymentEvent::RESULT_REJECTED, 'message' => 'Уведомление не с адреса ЮKassa', 'ip' => $request->ip(),
        ]);
        return response('', 403);
    }

    if (!is_string($paymentId) || !preg_match('/^[\w-]{10,64}$/', $paymentId)) {
        return response('', 400);
    }

    app(PaymentProcessor::class)->handle($paymentId, 'webhook', $payload, $request->ip());

    // 200 и на повтор, иначе ЮKassa будет слать уведомление ещё сутки
    return response('', 200);
});

Route::group(['prefix' => 'sandbox/yookassa'], function () {
    $find = function ($id) {
        $payment = Config::get('z1nex.shop::payment.driver') === 'sandbox' ? SandboxPayment::find($id) : null;
        if (!$payment) {
            abort(404);
        }
        return $payment;
    };

    Route::get('{id}', function ($id) use ($find) {
        return View::make('z1nex.shop::sandbox.checkout', [
            'payment' => $find($id),
            'action'  => Url::to('sandbox/yookassa/' . $id),
            'webhook' => Url::to('shop/webhook/yookassa'),
        ]);
    });

    Route::get('{id}/notification', function ($id) use ($find) {
        return SandboxGateway::notification($find($id));
    });

    Route::post('{id}/{action}', function ($id, $action) use ($find) {
        $payment = $find($id);

        if ($payment->status === 'pending') {
            $payment->status = $action === 'pay' ? 'succeeded' : 'canceled';
            $payment->save();
        }

        return [
            'notification' => SandboxGateway::notification($payment),
            'return_url'   => $payment->return_url,
        ];
    })->where('action', 'pay|cancel');
});
