<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('telegram')->nullable();
            $table->string('email')->nullable();
            $table->string('api')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('status')->default('new'); // obsolete

            $table->boolean('valid')->nullable(); // If balance and small order passed
            $table->boolean('active')->nullable(); // If checked by checkbox by user

            $table->string('balance_symbols')->nullable();

            $table->decimal('funds', 8, 4)->nullable();
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
        Schema::dropIfExists('clients');
    }
}
