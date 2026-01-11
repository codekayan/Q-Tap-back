<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qtap_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('email')->unique();
            $table->date('birth_date')->nullable();
            $table->string('country')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('img')->nullable();
            $table->enum('user_type', ['qtap_admins', 'qtap_clients','qtap_affiliates'])->default('qtap_admins');
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });


        DB::table('qtap_admins')->insert([
            'name' => 'Super Admin',
            'mobile' => '1234567890',
            'email' => 'admin@gmail.com',
            'birth_date' => '1990-01-01',
            'country' => 'SA',
            'password' => Hash::make('1'), // تأكد من استخدام Hash
            'user_type' => 'qtap_admins',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qtap_admins');
    }
};
