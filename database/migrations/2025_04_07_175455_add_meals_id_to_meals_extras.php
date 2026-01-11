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
        Schema::table('meals_extras', function (Blueprint $table) {
            $table->unsignedBigInteger('variants_id')->nullable()->change();
          


            $table->unsignedBigInteger('meals_id');
            $table->foreign('meals_id')->references('id')->on('meals')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meals_extras', function (Blueprint $table) {
            //
        });
    }
};
