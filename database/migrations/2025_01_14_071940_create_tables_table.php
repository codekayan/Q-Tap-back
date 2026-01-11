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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brunch_id')->referances('id')->on('qtap_clients_brunchs')->onDelete('cascade');
            $table->unsignedBigInteger('area_id')->referances('id')->on('areas')->onDelete('cascade');
            $table->string('name');
            $table->string('size');
            $table->string('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
