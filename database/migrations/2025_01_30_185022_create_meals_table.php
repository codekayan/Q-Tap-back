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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('img')->nullable();
            $table->string('Brief')->nullable();
            $table->string('Description')->nullable();
            $table->string('Ingredients')->nullable();
            $table->string('Calories')->nullable();
            $table->string('Time')->nullable();
            $table->string('Tax')->nullable();
            $table->string('price');
             $table->integer('discount_precentage')->default(0)->nullable();

            // Foreign key for discounts

            // Foreign key for categories
            $table->unsignedBigInteger('categories_id');
            $table->foreign('categories_id')->references('id')->on('meals_categories')->onDelete('cascade');

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
        Schema::dropIfExists('meals');
    }
};
