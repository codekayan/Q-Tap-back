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
        Schema::table('delivery_riders', function (Blueprint $table) {
            $table->enum('role', [ 'delivery_rider'])->default('delivery_rider');
            $table->enum('user_type', [ 'qtap_clients'])->default('qtap_clients');
            $table->string('email');
            $table->string('password');

            // مفتاح أجنبي للفروع
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('qtap_clients')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_riders', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
