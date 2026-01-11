<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('qtap_affiliates', function (Blueprint $table) {
            $table->dropColumn('clicks');
        });
    }



    public function down(): void
    {
        Schema::table('qtap_affiliates', function (Blueprint $table) {
            $table->integer('clicks')->default(0);
        });
    }
};
