<?php namespace Z1nex\Shop\Components;

use Db;
use Config;
use Redirect;
use Validator;
use ValidationException;
use Cms\Classes\ComponentBase;
use Z1nex\Shop\Classes\Cart as CartStore;
use Z1nex\Shop\Classes\Delivery\CdekClient;
use Z1nex\Shop\Classes\Payments\PaymentProcessor;
use Z1nex\Shop\Models\Order;

class Checkout extends ComponentBase
{
    public $cart;
    public $delivery;
    public $quotes;
    public $points;

    public function componentDetails()
    {
        return [
            'name'        => 'Оформление заказа',
            'description' => 'Расчёт доставки СДЭК, создание заказа и переход к оплате',
        ];
    }

    public function defineProperties()
    {
        return [
            'orderPage' => [
                'title'   => 'Страница заказа',
                'type'    => 'string',
                'default' => 'order',
            ],
        ];
    }

    public function onRun()
    {
        $this->prepare();
        if ($this->cart->isEmpty()) {
            return Redirect::to($this->controller->pageUrl('cart'));
        }
    }

    public function onSuggestCity()
    {
        $cities = app(CdekClient::class)->suggestCities(post('city_query'));
        return ['#city-suggest' => $this->renderPartial('@suggest', ['cities' => $cities])];
    }

    public function onSelectCity()
    {
        $cart = CartStore::instance();
        $code = (int) post('city_code');
        if (!$code) {
            throw new ValidationException(['city_query' => 'Выберите город из списка']);
        }

        $cdek = app(CdekClient::class);
        $quotes = $cdek->tariffs($code, $cart->weight(), Config::get('z1nex.shop::package'));

        $cart->resetDelivery([
            'city_code' => $code,
            'city'      => post('city_name'),
            'quotes'    => $quotes,
            'points'    => $quotes['pvz'] ? $cdek->deliveryPoints($code) : [],
        ]);

        return $this->refresh(['#city-suggest' => '']);
    }

    public function onSelectTariff()
    {
        $cart = CartStore::instance();
        list($method, $code) = array_pad(explode(':', (string) post('tariff')), 2, null);

        if ($method === 'pickup') {
            $cart->setDelivery(['method' => 'pickup', 'tariff' => null, 'tariff_name' => 'Самовывоз', 'price' => 0, 'days' => null]);
            return $this->refresh();
        }

        $group = $method === 'cdek_courier' ? 'courier' : 'pvz';
        $tariff = collect(array_get($cart->delivery(), "quotes.{$group}", []))->firstWhere('code', (int) $code);

        // цену берём из своего расчёта в сессии, а не из формы
        if (!$tariff) {
            throw new ValidationException(['tariff' => 'Тариф устарел, выберите город заново']);
        }

        $cart->setDelivery([
            'method'      => $method,
            'tariff'      => $tariff['code'],
            'tariff_name' => $tariff['name'],
            'price'       => $tariff['price'],
            'days'        => $tariff['days_min'] . '–' . $tariff['days_max'],
        ]);

        return $this->refresh();
    }

    public function onPlaceOrder()
    {
        $cart = CartStore::instance();
        $delivery = $cart->delivery();
        $data = post();

        if ($cart->isEmpty()) {
            throw new ValidationException(['cart' => 'Корзина пуста']);
        }

        $method = array_get($delivery, 'method');
        $rules = [
            'name'  => 'required|max:120',
            'phone' => ['required', 'regex:/^\+?[\d\s\-()]{10,20}$/'],
            'email' => 'required|email|max:120',
        ];
        if ($method === 'cdek_courier') {
            $rules['address'] = 'required|max:255';
        }
        if ($method === 'cdek_pvz') {
            $rules['point'] = 'required';
        }

        $validator = Validator::make($data, $rules, [
            'name.required'    => 'Укажите имя',
            'phone.required'   => 'Укажите телефон',
            'phone.regex'      => 'Телефон в формате +7 900 000-00-00',
            'email.required'   => 'Укажите почту для чека',
            'email.email'      => 'Почта указана с ошибкой',
            'address.required' => 'Укажите адрес для курьера',
            'point.required'   => 'Выберите пункт выдачи',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        if (!$method || ($method !== 'pickup' && empty($delivery['tariff']))) {
            throw new ValidationException(['tariff' => 'Выберите способ доставки']);
        }

        $point = null;
        if ($method === 'cdek_pvz') {
            $point = collect(array_get($delivery, 'points', []))->firstWhere('code', $data['point']);
            if (!$point) {
                throw new ValidationException(['point' => 'Пункт выдачи не найден, выберите снова']);
            }
        }

        $order = Db::transaction(function () use ($cart, $delivery, $data, $method, $point) {
            $order = new Order;
            $order->fill([
                'customer_name'          => $data['name'],
                'phone'                  => $data['phone'],
                'email'                  => $data['email'],
                'comment'                => array_get($data, 'comment'),
                'delivery_method'        => $method,
                'delivery_city'          => array_get($delivery, 'city'),
                'delivery_city_code'     => array_get($delivery, 'city_code'),
                'delivery_tariff'        => array_get($delivery, 'tariff'),
                'delivery_tariff_name'   => array_get($delivery, 'tariff_name'),
                'delivery_point'         => $point ? $point['code'] : null,
                'delivery_point_address' => $point ? $point['address'] : null,
                'delivery_address'       => $method === 'cdek_courier' ? $data['address'] : null,
                'delivery_price'         => $cart->deliveryPrice(),
                'delivery_days'          => array_get($delivery, 'days'),
            ]);
            $order->status = Order::STATUS_NEW;
            $order->items_total = $cart->itemsTotal();
            $order->total = $cart->total();
            $order->save();

            foreach ($cart->lines() as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'name'       => $line->product->name,
                    'price'      => $line->product->price,
                    'quantity'   => $line->quantity,
                    'weight'     => $line->product->weight,
                ]);
            }

            return $order;
        });

        $returnUrl = $this->controller->pageUrl($this->property('orderPage'), ['hash' => $order->hash]);
        $paymentUrl = app(PaymentProcessor::class)->start($order, $returnUrl);

        $cart->clear();

        return Redirect::to($paymentUrl);
    }

    protected function refresh(array $extra = [])
    {
        $this->prepare();

        return $extra + [
            '#delivery-options' => $this->renderPartial('@delivery'),
            '#checkout-summary' => $this->renderPartial('@summary'),
        ];
    }

    protected function prepare()
    {
        $this->cart = CartStore::instance();
        $this->delivery = $this->cart->delivery();
        $this->quotes = array_get($this->delivery, 'quotes');
        $this->points = array_get($this->delivery, 'points', []);
    }
}
