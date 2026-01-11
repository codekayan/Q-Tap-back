<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qtap_clients_brunchs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('qtap_clients')->onDelete('cascade');


            $table->unsignedBigInteger('currency_id');
         //   $table->unsignedBigInteger('pricing_id');
            $table->unsignedBigInteger('discount_id')->nullable();

            $table->foreign('currency_id')->references('id')->on('currencies');
          //  $table->foreign('pricing_id')->references('id')->on('pricings');
            $table->foreign('discount_id')->references('id')->on('discounts');


            $table->enum('payment_method' , ['cash', 'wallet']);

            $table->string('business_name');
            $table->string('business_country');
            $table->string('business_city');

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('business_format', ['UL', 'UK']);
            $table->enum('menu_design', ['Grid', 'list']);
            $table->enum('default_mode', ['dark', 'white']);
            $table->enum('payment_time', ['before', 'after']);
            $table->enum('call_waiter', ['active', 'inactive']);



            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qtap_clients_brunchs');
    }
};
