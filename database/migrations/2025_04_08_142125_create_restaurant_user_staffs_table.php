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
        Schema::create('restaurant_user_staffs', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('pin');

            $table->enum('user_type', ['qtap_admin', 'qtap_clients', 'qtap_affiliate'])->default('qtap_clients');

            // مفتاح أجنبي للأدوار
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');


            //role
            $table->string('role')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            // مفتاح أجنبي للفروع
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('qtap_clients')->onDelete('cascade');


            // مفتاح أجنبي للفروع
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_user_staffs');
    }
};
