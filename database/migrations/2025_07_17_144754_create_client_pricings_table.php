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
        Schema::create('client_pricings', function (Blueprint $table) {
            $table->id();

            // العلاقات
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('qtap_clients')->onDelete('cascade');

            $table->unsignedBigInteger('pricing_id');
            $table->foreign('pricing_id')->references('id')->on('pricings');

            // الحقول الأساسية
            $table->string('ramin_order');
            $table->string('payment_methodes');
            $table->string('pricing_way');
            $table->string('status')->default('pending');
            $table->string('image')->nullable();
            $table->dateTime('expired_at')->nullable();

            // الحقول الجديدة للأسعار والخصم
            $table->decimal('original_price', 10, 2);
            $table->decimal('original_total_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discounted_price', 10, 2);
            $table->decimal('final_price', 10, 2);
            $table->string('coupon_code')->nullable();
            $table->integer('number_of_branches');
            $table->json('discount_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_pricings');
    }
};
