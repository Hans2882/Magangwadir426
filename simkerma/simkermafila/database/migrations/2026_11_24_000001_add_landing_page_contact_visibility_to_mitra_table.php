<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra', function (Blueprint $table) {
            $table->boolean('tampilkan_telepon')->default(true);
            $table->boolean('tampilkan_email')->default(true);
            $table->boolean('tampilkan_alamat')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('mitra', function (Blueprint $table) {
            $table->dropColumn([
                'tampilkan_telepon',
                'tampilkan_email',
                'tampilkan_alamat',
            ]);
        });
    }
};
