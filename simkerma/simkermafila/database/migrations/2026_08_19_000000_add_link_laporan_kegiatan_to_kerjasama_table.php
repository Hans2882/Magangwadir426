<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->string('link_laporan_kegiatan', 500)->nullable()->after('bukti_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->dropColumn('link_laporan_kegiatan');
        });
    }
};