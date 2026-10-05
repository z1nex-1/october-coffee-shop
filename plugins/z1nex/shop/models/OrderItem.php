<?php namespace Z1nex\Shop\Models;

use Model;

class OrderItem extends Model
{
    public $table = 'z1nex_shop_order_items';

    public $timestamps = false;

    protected $fillable = ['product_id', 'name', 'price', 'quantity', 'weight'];

    public $belongsTo = [
        'order'   => Order::class,
        'product' => Product::class,
    ];

    public function getSumAttribute()
    {
        return $this->price * $this->quantity;
    }
}
