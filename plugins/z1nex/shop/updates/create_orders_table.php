<?php namespace Z1nex\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('z1nex_shop_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('number', 32)->unique();
            $table->string('hash', 32)->unique();
            $table->string('status', 24)->default('new')->index();
            $table->string('customer_name');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->text('comment')->nullable();
            $table->string('delivery_method', 24);
            $table->string('delivery_city')->nullable();
            $table->unsignedInteger('delivery_city_code')->nullable();
            $table->unsignedInteger('delivery_tariff')->nullable();
            $table->string('delivery_tariff_name')->nullable();
            $table->string('delivery_point', 32)->nullable();
            $table->string('delivery_point_address')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedInteger('delivery_price')->default(0);
            $table->string('delivery_days', 16)->nullable();
            $table->unsignedInteger('items_total')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('payment_id', 64)->nullable()->index();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('z1nex_shop_order_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('order_id')->index();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('weight')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('z1nex_shop_order_items');
        Schema::dropIfExists('z1nex_shop_orders');
    }
}
