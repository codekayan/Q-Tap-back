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
        Schema::create('payment_services', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['cash', 'wallet', 'card']);
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_services');
    }
};
