<?php namespace Z1nex\Shop\Classes;

use Config;
use Session;
use Z1nex\Shop\Models\Product;

class Cart
{
    const ITEMS_KEY = 'z1nex_shop.cart';
    const DELIVERY_KEY = 'z1nex_shop.delivery';
    const MAX_QUANTITY = 20;

    protected $lines;

    public static function instance()
    {
        static $instance;
        return $instance ?: $instance = new static;
    }

    public function add($productId, $quantity = 1)
    {
        $items = $this->raw();
        $current = array_get($items, $productId, 0);
        return $this->set($productId, $current + $quantity);
    }

    public function set($productId, $quantity)
    {
        $items = $this->raw();
        $quantity = min(self::MAX_QUANTITY, (int) $quantity);

        if ($quantity <= 0) {
            unset($items[$productId]);
        }
        elseif (Product::active()->where('id', $productId)->exists()) {
            $items[$productId] = $quantity;
        }

        Session::put(self::ITEMS_KEY, $items);
        $this->lines = null;

        // вес изменился — старый расчёт доставки уже неверен
        $this->forgetDeliveryQuote();

        return $this;
    }

    public function clear()
    {
        Session::forget(self::ITEMS_KEY);
        Session::forget(self::DELIVERY_KEY);
        $this->lines = null;
    }

    public function lines()
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->raw();
        $products = Product::active()->whereIn('id', array_keys($items))->get();

        return $this->lines = $products->map(function ($product) use ($items) {
            $quantity = $items[$product->id];
            return (object) [
                'product'  => $product,
                'quantity' => $quantity,
                'sum'      => $product->price * $quantity,
            ];
        })->values()->all();
    }

    public function isEmpty()
    {
        return count($this->lines()) === 0;
    }

    public function count()
    {
        return array_sum(array_map(function ($line) { return $line->quantity; }, $this->lines()));
    }

    public function itemsTotal()
    {
        return array_sum(array_map(function ($line) { return $line->sum; }, $this->lines()));
    }

    public function weight()
    {
        $grams = array_sum(array_map(function ($line) {
            return $line->product->weight * $line->quantity;
        }, $this->lines()));

        return $grams + Config::get('z1nex.shop::package.tare_grams');
    }

    public function isFreeDelivery()
    {
        return $this->itemsTotal() >= Config::get('z1nex.shop::free_delivery_from');
    }

    public function untilFreeDelivery()
    {
        return max(0, Config::get('z1nex.shop::free_delivery_from') - $this->itemsTotal());
    }

    public function delivery()
    {
        return Session::get(self::DELIVERY_KEY, []);
    }

    public function setDelivery(array $data)
    {
        Session::put(self::DELIVERY_KEY, array_merge($this->delivery(), $data));
    }

    public function resetDelivery(array $data)
    {
        Session::put(self::DELIVERY_KEY, $data);
    }

    public function forgetDeliveryQuote()
    {
        $delivery = $this->delivery();
        unset($delivery['tariff'], $delivery['tariff_name'], $delivery['price'], $delivery['days'], $delivery['quotes']);
        Session::put(self::DELIVERY_KEY, $delivery);
    }

    public function deliveryPrice()
    {
        $delivery = $this->delivery();
        if (array_get($delivery, 'method') === 'pickup' || $this->isFreeDelivery()) {
            return 0;
        }
        return (int) array_get($delivery, 'price', 0);
    }

    public function total()
    {
        return $this->itemsTotal() + $this->deliveryPrice();
    }

    public function toArray()
    {
        return [
            'count'     => $this->count(),
            'itemsTotal'=> $this->itemsTotal(),
            'lines'     => array_map(function ($line) {
                return [
                    'id'       => $line->product->id,
                    'name'     => $line->product->name,
                    'weight'   => $line->product->weight_label,
                    'color'    => $line->product->color,
                    'price'    => $line->product->price,
                    'quantity' => $line->quantity,
                    'sum'      => $line->sum,
                ];
            }, $this->lines()),
        ];
    }

    protected function raw()
    {
        return (array) Session::get(self::ITEMS_KEY, []);
    }
}
