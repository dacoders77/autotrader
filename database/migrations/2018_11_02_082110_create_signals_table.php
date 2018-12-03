<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSignalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('symbol')->nullable();
            $table->decimal('multiplier', 12,6)->nullable();
            $table->decimal('percent')->nullable()->nullable();
            $table->decimal('leverage')->nullable()->nullable();
            $table->string('direction')->nullable()->nullable();


            $table->decimal('quote_value', 16, 8)->nullable();
            $table->string('quote_response')->nullable();
            $table->string('quote_status')->nullable();



            $table->string('status')->default('new')->nullable();

            $table->dateTime('open_date')->nullable();
            $table->decimal('open_price', 16, 8)->nullable();
            $table->dateTime('close_date')->nullable();
            $table->decimal('close_price', 16, 8)->nullable();

            $table->decimal('stop_loss_price')->nullable();
            $table->decimal('stop_loss_percent')->nullable();

            $table->string('info')->nullable();
            $table->boolean('is_deleted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signals');
    }
}
