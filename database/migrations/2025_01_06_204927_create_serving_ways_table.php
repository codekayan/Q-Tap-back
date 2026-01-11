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
        Schema::create('serving_ways', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['dine_in', 'take_away', 'delivery']);
            $table->unsignedBigInteger('brunch_id');
            $table->unsignedBigInteger('tables_number')->nullable();
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
        Schema::dropIfExists('serving_ways');
    }
};
