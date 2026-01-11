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
        Schema::create('customers__visits_restaurants', function (Blueprint $table) {
            $table->id();

            $table->integer('visits')->default(0);

            //brunch_id
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_visits_restaurants');
    }
};
