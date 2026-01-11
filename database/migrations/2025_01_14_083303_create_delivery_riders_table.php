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
        Schema::create('delivery_riders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brunch_id')->referances('id')->on('qtap_clients_brunchs')->onDelete('cascade');
            $table->unsignedBigInteger('delivery_areas_id')->referances('id')->on('delivery_areas')->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('pin');
            $table->double('orders')->default(0);
            $table->enum('status', ['Available', 'Busy']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_riders');
    }
};
