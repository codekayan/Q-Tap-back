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
        Schema::create('language_preferences', function (Blueprint $table) {
        $table->id();
        $table->string('language', 10)->default('ar');
        $table->morphs('languageable'); // Creates languageable_id and languageable_type
        $table->timestamps();

        $table->index(['languageable_id', 'languageable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_preferences');
    }
};
