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
        Schema::create('ticket_supports', function (Blueprint $table) {
            $table->id();
            $table->string('Customer_Name');
            $table->integer('client_id');
            $table->integer('brunch_id');
            $table->string('Customer_Email');
            $table->string('Customer_Phone');
            $table->enum('status', ['open', 'in_progress', 'Done'])->default('open');
            $table->string('content');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_supports');
    }
};
