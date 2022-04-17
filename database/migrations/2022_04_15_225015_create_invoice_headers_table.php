<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_headers', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->time('invoice_time');
            $table->unsignedBigInteger('invoice_transmitter_id');
            $table->foreign('invoice_transmitter_id')->references('id')->on('users');
            $table->unsignedBigInteger('invoice_receiver_id');
            $table->foreign('invoice_receiver_id')->references('id')->on('users');
            $table->string('invoice_value_before_vat');
            $table->double('invoice_vat');
            $table->string('invoice_total_value');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_headers');
    }
};
