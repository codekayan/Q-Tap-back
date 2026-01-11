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
        Schema::table('meals', function (Blueprint $table) {
            $table->string('price_small')->nullable();
            $table->string('price_medium')->nullable();
            $table->string('price_large')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn('price_small');
            $table->dropColumn('price_medium');
            $table->dropColumn('price_large');
        });
    }
};
