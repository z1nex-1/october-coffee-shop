<?php namespace Z1nex\Shop\Components;

use Cms\Classes\ComponentBase;
use Z1nex\Shop\Classes\Cart as CartStore;

class Cart extends ComponentBase
{
    public $cart;

    public function componentDetails()
    {
        return [
            'name'        => 'Корзина',
            'description' => 'Корзина в сессии и данные для мини-корзины',
        ];
    }

    public function onRun()
    {
        $this->cart = CartStore::instance();
    }

    public function onAdd()
    {
        CartStore::instance()->add((int) post('id'), max(1, (int) post('quantity', 1)));
        return $this->respond();
    }

    public function onSetQty()
    {
        CartStore::instance()->set((int) post('id'), (int) post('quantity'));
        return $this->respond();
    }

    public function onRemove()
    {
        CartStore::instance()->set((int) post('id'), 0);
        return $this->respond();
    }

    public function onGetCart()
    {
        return ['cart' => CartStore::instance()->toArray()];
    }

    protected function respond()
    {
        $this->cart = CartStore::instance();

        return [
            'cart' => $this->cart->toArray(),
            '#cart-page' => $this->renderPartial('@lines'),
        ];
    }
}
