<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExecutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('executions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('signal_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('client_name')->nullable();

            $table->string('symbol')->nullable();
            //$table->decimal('multiplier', 12,6)->nullable();
            $table->string('direction')->nullable();
            $table->decimal('client_volume', 12,6)->nullable();
            $table->decimal('percent')->nullable();
            $table->integer('leverage')->nullable();

            $table->string('status')->nullable();
            $table->string('in_status')->nullable();
            $table->string('out_status')->nullable();



            $table->string('client_funds_status')->nullable();
            $table->text('client_funds_response')->nullable();
            $table->decimal('client_funds_value', 12,6)->nullable();

            $table->string('leverage_status')->nullable();
            $table->text('leverage_response')->nullable();
            $table->decimal('leverage_value', 12,6)->nullable();

            $table->string('small_order_status')->nullable();
            $table->text('small_order_response')->nullable();
            $table->decimal('small_order_value', 12,6)->nullable();

            $table->string('in_place_order_status')->nullable();
            $table->text('in_place_order_response')->nullable();
            $table->decimal('in_place_order_value', 12,6)->nullable();

            $table->string('in_balance_status')->nullable();
            $table->text('in_balance_response')->nullable();
            $table->integer('in_balance_value')->nullable();

            $table->string('out_place_order_status')->nullable();
            $table->text('out_place_order_response')->nullable();
            $table->decimal('out_place_order_value', 12,6)->nullable();

            $table->string('out_balance_status')->nullable();
            $table->text('out_balance_response')->nullable();
            $table->integer('out_balance_value')->nullable();



            $table->text('close_funds_response')->nullable();
            $table->string('open_status')->nullable();
            $table->string('close_status')->nullable();
            $table->decimal('open_price', 16,8)->nullable();
            $table->decimal('close_price', 16, 8)->nullable();
            $table->text('open_response')->nullable();
            $table->text('close_response')->nullable();
            $table->string('info', 10000)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('executions');
    }
}
