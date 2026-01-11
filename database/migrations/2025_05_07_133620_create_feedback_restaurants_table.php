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
        Schema::create('feedback_restaurants', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id')->nullable();
            // brunch id forgn key
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');

            $table->integer('star');
            $table->enum('emoji', ['very happy', 'happy', 'said']);
            $table->enum('your_goals', ['yes', 'no']);
            $table->enum('publish', ['yes', 'no'])->default('no');
            $table->text('missing_Q-tap_Menus');
            $table->text('comment');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_restaurants');
    }
};
