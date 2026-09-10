<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_award_periods', function (Blueprint $table) {
            $table->json('konfigurasi_penilaian')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('mitra_award_periods', function (Blueprint $table) {
            $table->dropColumn('konfigurasi_penilaian');
        });
    }
};