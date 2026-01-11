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
        Schema::table('restaurant_user_staffs', function (Blueprint $table) {

            $table->unsignedBigInteger('delivery_areas_id')->nullable();
            $table->foreign('delivery_areas_id')->references('id')->on('delivery_areas')->onDelete('restrict');


            $table->string('phone')->nullable();

            $table->enum('status_rider', ['Available', 'Busy'])->default('Busy');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_user_staffs', function (Blueprint $table) {
            $table->dropColumn(['delivery_areas_id', 'phone', 'status_rider']);
        });
    }
};
