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
        Schema::table('ticket_supports', function (Blueprint $table) {
            // تعديل الأعمدة لتكون متوافقة مع الأعمدة في الجداول المرجعية
            $table->unsignedBigInteger('client_id')->change(); // تعديل نوع العمود client_id
            $table->unsignedBigInteger('brunch_id')->change(); // تعديل نوع العمود brunch_id

                // تعريف المفتاح الأجنبي client_id
                $table->foreign('client_id')->references('id')->on('qtap_clients')->onDelete('cascade');

                // تعريف المفتاح الأجنبي brunch_id
                $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_supports', function (Blueprint $table) {
            //
        });
    }
};
