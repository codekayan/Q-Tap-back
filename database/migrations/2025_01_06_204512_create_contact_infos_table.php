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
        Schema::create('contact_infos', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');

            $table->text('business_email')->nullable();
            $table->text('business_phone')->nullable();
            $table->text('website')->nullable();
            $table->text('facebook')->nullable();
            $table->text('twitter')->nullable();
            $table->text('instagram')->nullable();
            $table->text('address')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_infos');
    }
};
