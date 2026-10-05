<?php namespace Z1nex\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePaymentEventsTable extends Migration
{
    public function up()
    {
        Schema::create('z1nex_shop_payment_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('order_id')->nullable()->index();
            $table->string('source', 16);
            $table->string('event', 48)->nullable();
            $table->string('payment_id', 64)->nullable()->index();
            $table->string('result', 16);
            $table->string('message')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('payload')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('z1nex_shop_payment_events');
    }
}
