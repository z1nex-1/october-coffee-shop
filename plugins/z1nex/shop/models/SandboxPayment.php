<?php namespace Z1nex\Shop\Models;

use Model;

class SandboxPayment extends Model
{
    public $table = 'z1nex_shop_sandbox_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'status', 'amount', 'description', 'metadata', 'return_url', 'idempotence_key'];

    protected $jsonable = ['metadata'];
}
