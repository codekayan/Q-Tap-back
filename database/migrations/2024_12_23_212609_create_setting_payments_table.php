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
        Schema::create('setting_payments', function (Blueprint $table) {
            $table->id();
            $table->text('API_KEY');
            $table->text('IFRAME_ID');
            $table->text('INTEGRATION_ID');
            $table->text('HMAC');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_payments');
    }
};
