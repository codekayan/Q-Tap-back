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
        Schema::create('affiliate_payment_infos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('affiliate_id')->constrained('qtap_affiliates')->onDelete('cascade');

            $table->enum('payment_way', ['bank_account', 'wallet', 'credit_card']);

            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            $table->string('wallet_provider')->nullable();
            $table->string('wallet_number')->nullable();

            $table->string('name_on_credit_card')->nullable();
            $table->string('credit_card_number')->nullable();
            $table->date('address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_payment_infos');
    }
};
