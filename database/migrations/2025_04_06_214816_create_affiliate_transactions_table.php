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
        Schema::create('affiliate_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');
            $table->foreign('affiliate_id')->references('id')->on('qtap_affiliates')->onDelete('cascade');



            $table->double('amount');

            $table->double('Reverence_no')->nullable();
            $table->enum('status', ['pending', 'Done', 'failed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_transactions');
    }
};
