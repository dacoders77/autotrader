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

            // execution date
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
