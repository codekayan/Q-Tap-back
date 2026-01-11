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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // customer info
            $table->string('name');
            $table->string('phone');
            $table->text('comments');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            //delivery info
            $table->string('city')->nullable();
            $table->string('address')->nullable();

            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();


            //dine in info
            //table id
            $table->unsignedBigInteger('table_id')->nullable();
            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');

            //type order
            $table->enum('type', ['dinein', 'takeaway', 'delivery']);

            //status order
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'rejected', 'cancelled'])->default('pending');


            //discount code
            $table->json('discount_code')->nullable();

            //tax
            $table->double('tax')->nullable();

            //reference number
            $table->string('reference_number')->nullable();

            //total price
            $table->double('total_price')->nullable();

            //  payment way
            $table->enum('payment_way', ['cash', 'wallet']);

            //meal id
            $table->json('meal_id')->nullable();

            $table->json('variants')->nullable();
            $table->json('extras')->nullable();

            //size id
            $table->unsignedBigInteger('size_id')->nullable();
            $table->foreign('size_id')->references('id')->on('meals_sizes')->onDelete('cascade');

            //quantity
            $table->json('quantity')->nullable();


            //brunch id
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
