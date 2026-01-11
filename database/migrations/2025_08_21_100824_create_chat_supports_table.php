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
        Schema::create('chat_supports', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('status')->default('pending'); // أو حسب الحالة الافتراضية التي تريدها
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_supports');
    }
};
