<?php namespace Z1nex\Shop\Models;

use Model;
use Carbon\Carbon;

class Order extends Model
{
    const STATUS_NEW = 'new';
    const STATUS_PENDING = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SHIPPED = 'shipped';

    public $table = 'z1nex_shop_orders';

    protected $fillable = [
        'customer_name', 'phone', 'email', 'comment',
        'delivery_method', 'delivery_city', 'delivery_city_code',
        'delivery_tariff', 'delivery_tariff_name', 'delivery_point',
        'delivery_point_address', 'delivery_address', 'delivery_price', 'delivery_days',
    ];

    protected $dates = ['paid_at'];

    public $hasMany = [
        'items'  => [OrderItem::class, 'delete' => true],
        'events' => [PaymentEvent::class, 'order' => 'id desc'],
    ];

    public static $statuses = [
        self::STATUS_NEW      => 'новый',
        self::STATUS_PENDING  => 'ждёт оплаты',
        self::STATUS_PAID     => 'оплачен',
        self::STATUS_CANCELED => 'отменён',
        self::STATUS_SHIPPED  => 'передан в доставку',
    ];

    public static $deliveryMethods = [
        'cdek_pvz'     => 'СДЭК, пункт выдачи',
        'cdek_courier' => 'СДЭК, курьер',
        'pickup'       => 'Самовывоз из обжарочной',
    ];

    public function beforeCreate()
    {
        $this->hash = str_random(24);
        $this->number = Carbon::now()->format('ymd') . '-' . strtoupper(str_random(4));
    }

    public function getStatusOptions()
    {
        return self::$statuses;
    }

    public function getDeliveryMethodOptions()
    {
        return self::$deliveryMethods;
    }

    public function getStatusLabelAttribute()
    {
        return array_get(self::$statuses, $this->status, $this->status);
    }

    public function getDeliveryMethodLabelAttribute()
    {
        return array_get(self::$deliveryMethods, $this->delivery_method, $this->delivery_method);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function idempotenceKey()
    {
        return 'order-' . $this->id . '-' . $this->hash;
    }

    public function recalculate()
    {
        $this->items_total = $this->items()->get()->sum('sum');
        $this->total = $this->items_total + $this->delivery_price;
    }
}
