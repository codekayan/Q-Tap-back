<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['admin', 'chef', 'cashier', 'waiter']);
            $table->integer('menu')->default(0);
            $table->integer('users')->default(0);
            $table->integer('orders')->default(0);
            $table->integer('wallet')->default(0);
            $table->integer('setting')->default(0);
            $table->integer('support')->default(0);
            $table->integer('dashboard')->default(0);
            $table->integer('customers_log')->default(0);

            // Foreign key for branches
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
        Schema::dropIfExists('roles');
    }
};
