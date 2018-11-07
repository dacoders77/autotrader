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

            $table->string('signal_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_name')->nullable();

            $table->string('symbol')->nullable();
            $table->decimal('multiplier', 12,6)->nullable();
            $table->string('direction')->nullable();
            $table->decimal('client_volume', 12,6)->nullable();
            $table->decimal('percent')->nullable();
            $table->decimal('leverage')->nullable();

            $table->decimal('client_funds', 12,6)->nullable();


            $table->string('status')->nullable();
            $table->string('open_status')->nullable();
            $table->string('close_status')->nullable();
            $table->decimal('open_price')->nullable();
            $table->decimal('close_price')->nullable();
            $table->string('open_response', 1000)->nullable();
            $table->string('close_response', 1000)->nullable();
            $table->string('info', 10000)->nullable();

            // open_status (take from response)
            // close_status
            // signal id
            // client id
            // client name
            // symbol
            // direction
            // volume
            // %
            // leverage
            // open price
            // close price
            // open_response (full)
            // close_response (full)
            // info


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
