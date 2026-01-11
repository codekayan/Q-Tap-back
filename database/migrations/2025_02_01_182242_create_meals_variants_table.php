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
        Schema::create('meals_variants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('price');
            // Foreign key for meals
            $table->unsignedBigInteger('meals_id');
            $table->foreign('meals_id')->references('id')->on('meals')->onDelete('cascade');

            // Foreign key for branches
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
        Schema::dropIfExists('meals_variants');
    }
};
