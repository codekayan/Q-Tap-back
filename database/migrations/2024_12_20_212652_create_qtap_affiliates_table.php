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
        Schema::create('qtap_affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('email')->unique();
            $table->date('birth_date')->nullable();
            $table->string('country')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type');
            $table->string('img')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');

            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qtap_affiliates');
    }
};
