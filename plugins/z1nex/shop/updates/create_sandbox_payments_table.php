<?php namespace Z1nex\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSandboxPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('z1nex_shop_sandbox_payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('id', 36)->primary();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('amount');
            $table->string('description')->nullable();
            $table->text('metadata')->nullable();
            $table->string('return_url');
            $table->string('idempotence_key', 64)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('z1nex_shop_sandbox_payments');
    }
}
