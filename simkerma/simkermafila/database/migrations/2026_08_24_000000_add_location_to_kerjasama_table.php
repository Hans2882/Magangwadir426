<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kerjasama', function (Blueprint $table): void {
            $table->foreignId('provinsi_id')
                ->nullable()
                ->after('mitra_id')
                ->constrained('master_provinsi')
                ->nullOnDelete();
            $table->foreignId('kota_id')
                ->nullable()
                ->after('provinsi_id')
                ->constrained('master_kota')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kerjasama', function (Blueprint $table): void {
            $table->dropForeign(['kota_id']);
            $table->dropForeign(['provinsi_id']);
            $table->dropColumn(['kota_id', 'provinsi_id']);
        });
    }
};