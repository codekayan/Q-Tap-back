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
        Schema::create('clients_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->referances('id')->on('qtap_clients')->onDelete('cascade');

            $table->double('amount');

            $table->double('Reverence_no')->nullable();
            $table->enum('status', ['pending', 'Done', 'failed'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients_transactions');
    }
};
