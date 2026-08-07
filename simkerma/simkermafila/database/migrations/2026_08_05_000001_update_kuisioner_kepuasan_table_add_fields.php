<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuisioner_kepuasan', function (Blueprint $table): void {
            $table->string('jabatan')->after('nama');
            $table->string('email')->after('instansi');
            $table->string('telepon')->after('email');
            $table->tinyInteger('komunikasi')->after('telepon');
            $table->tinyInteger('proses')->after('komunikasi');
            $table->tinyInteger('bantuan')->after('proses');
            $table->tinyInteger('sdm_profesionalisme')->after('bantuan');
            $table->tinyInteger('harapan')->after('sdm_profesionalisme');
            $table->tinyInteger('manfaat')->after('harapan');
            $table->tinyInteger('kembali')->after('manfaat');
            $table->tinyInteger('implementasi')->after('kembali');
            $table->tinyInteger('laporan')->after('implementasi');
            $table->string('alumni_ada')->after('laporan');
            $table->tinyInteger('etika')->after('alumni_ada');
            $table->tinyInteger('kepemimpinan')->after('etika');
            $table->tinyInteger('etos_kerja')->after('kepemimpinan');
            $table->tinyInteger('komunikasi_alumni')->after('etos_kerja');
            $table->tinyInteger('kerjasama_tim')->after('komunikasi_alumni');
            $table->tinyInteger('keahlian_bidang_ilmu')->after('kerjasama_tim');
            $table->tinyInteger('keahlian_bidang_ilmu_terapan')->after('keahlian_bidang_ilmu');
            $table->tinyInteger('bahasa_asing')->after('keahlian_bidang_ilmu_terapan');
            $table->tinyInteger('teknologi_informasi')->after('bahasa_asing');
            $table->tinyInteger('pengembangan_diri')->after('teknologi_informasi');
            $table->text('saran_kerjasama')->after('pengembangan_diri');
            $table->text('saran_alumni')->after('saran_kerjasama');
            $table->string('program_studi_alumni')->after('saran_alumni');
        });
    }

    public function down(): void
    {
        Schema::table('kuisioner_kepuasan', function (Blueprint $table): void {
            $table->dropColumn([
                'jabatan',
                'email',
                'telepon',
                'komunikasi',
                'proses',
                'bantuan',
                'sdm_profesionalisme',
                'harapan',
                'manfaat',
                'kembali',
                'implementasi',
                'laporan',
                'alumni_ada',
                'etika',
                'kepemimpinan',
                'etos_kerja',
                'komunikasi_alumni',
                'kerjasama_tim',
                'keahlian_bidang_ilmu',
                'keahlian_bidang_ilmu_terapan',
                'bahasa_asing',
                'teknologi_informasi',
                'pengembangan_diri',
                'saran_kerjasama',
                'saran_alumni',
                'program_studi_alumni',
            ]);
        });
    }
};
