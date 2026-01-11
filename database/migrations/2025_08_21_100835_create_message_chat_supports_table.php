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
        Schema::create('message_chat_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_support_id')->nullable()->constrained('chat_supports')->nullOnDelete();
            $table->text('message');
            $table->string('type'); // أو 'image', 'file' حسب نوع الرسالة
            $table->string('status')->default('pending'); // أو 'read', 'pending'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_chat_supports');
    }
};
