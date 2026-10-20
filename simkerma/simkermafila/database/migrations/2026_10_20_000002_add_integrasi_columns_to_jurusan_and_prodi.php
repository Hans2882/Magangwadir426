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
        // 1. Tambah integrasi_jurusan, nama_pimpinan, dan nip_pimpinan pada master_jurusans
        Schema::table('master_jurusans', function (Blueprint $table) {
            if (!Schema::hasColumn('master_jurusans', 'integrasi_jurusan')) {
                $table->string('integrasi_jurusan', 50)->nullable()->after('nama_jurusan_eng');
            }
            if (!Schema::hasColumn('master_jurusans', 'nama_pimpinan')) {
                $table->string('nama_pimpinan', 255)->nullable()->after('integrasi_jurusan');
            }
            if (!Schema::hasColumn('master_jurusans', 'nip_pimpinan')) {
                $table->string('nip_pimpinan', 50)->nullable()->after('nama_pimpinan');
            }
        });

        // 2. Pastikan integrasi_prodi pada master_program_studi tercatat di migration
        Schema::table('master_program_studi', function (Blueprint $table) {
            if (!Schema::hasColumn('master_program_studi', 'integrasi_prodi')) {
                $table->string('integrasi_prodi', 50)->nullable()->after('nama_prodi_eng');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_jurusans', function (Blueprint $table) {
            $cols = array_filter(['integrasi_jurusan', 'nama_pimpinan', 'nip_pimpinan'], fn($c) => Schema::hasColumn('master_jurusans', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('master_program_studi', function (Blueprint $table) {
            if (Schema::hasColumn('master_program_studi', 'integrasi_prodi')) {
                $table->dropColumn('integrasi_prodi');
            }
        });
    }
};
