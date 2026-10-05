<?php namespace Z1nex\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('z1nex_shop_products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->string('origin')->nullable();
            $table->string('process')->nullable();
            $table->string('roast', 16)->default('medium');
            $table->string('notes')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('weight')->default(250);
            $table->unsignedInteger('price');
            $table->string('color', 7)->default('#7a4b2a');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('z1nex_shop_products');
    }
}
