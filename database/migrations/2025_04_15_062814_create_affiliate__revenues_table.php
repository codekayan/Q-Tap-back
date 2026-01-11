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
        Schema::create('affiliate__revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id');
            $table->foreign('affiliate_id')->references('id')->on('qtap_affiliates')->onDelete('cascade');

            $table->string('order_id');

            $table->double('amount')->nullable();

            $table->string('commission');

            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');

            $table->string('affiliate_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate__revenues');
    }
};
