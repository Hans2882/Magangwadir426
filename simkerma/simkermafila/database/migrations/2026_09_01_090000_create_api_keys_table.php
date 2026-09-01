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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->comment('Nama API Key (untuk identifikasi)');
            $table->string('key', 255)->unique()->comment('API Key yang di-generate');
            $table->boolean('is_active')->default(true)->comment('Status aktif/non-aktif');
            $table->json('allowed_endpoints')->nullable()->comment('JSON array of allowed endpoints');
            $table->string('ip_whitelist')->nullable()->comment('IP yang diizinkan mengakses');
            $table->timestamp('last_used_at')->nullable()->comment('Waktu terakhir kali digunakan');
            $table->timestamps();
            
            $table->index('key');
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
