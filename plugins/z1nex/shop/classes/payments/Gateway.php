<?php namespace Z1nex\Shop\Classes\Payments;

use Z1nex\Shop\Models\Order;

interface Gateway
{
    /**
     * Возвращает ['id', 'status', 'confirmation_url'].
     */
    public function create(Order $order, $returnUrl);

    /**
     * Возвращает ['id', 'status', 'amount', 'order_id'] или null, если платёж не найден.
     */
    public function fetch($paymentId);

    public function name();
}
