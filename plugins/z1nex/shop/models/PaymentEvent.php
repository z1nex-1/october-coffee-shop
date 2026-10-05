<?php namespace Z1nex\Shop\Models;

use Model;

class PaymentEvent extends Model
{
    const UPDATED_AT = null;

    const RESULT_APPLIED = 'applied';
    const RESULT_DUPLICATE = 'duplicate';
    const RESULT_PENDING = 'pending';
    const RESULT_REJECTED = 'rejected';

    public $table = 'z1nex_shop_payment_events';

    protected $fillable = ['order_id', 'source', 'event', 'payment_id', 'result', 'message', 'ip', 'payload'];

    protected $jsonable = ['payload'];

    public $belongsTo = [
        'order' => Order::class,
    ];

    public static $results = [
        'applied'   => 'статус изменён',
        'duplicate' => 'повтор, пропущено',
        'pending'   => 'оплата ещё не завершена',
        'rejected'  => 'отклонено',
    ];

    public function getResultLabelAttribute()
    {
        return array_get(self::$results, $this->result, $this->result);
    }

    public function setUpdatedAt($value)
    {
        return $this;
    }
}
