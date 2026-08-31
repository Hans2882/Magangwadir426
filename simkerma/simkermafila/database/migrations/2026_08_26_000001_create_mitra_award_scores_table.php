<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra_award_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_award_period_id')->constrained()->cascadeOnDelete();
            $table->integer('mitra_id');
            $table->foreign('mitra_id')->references('id')->on('mitra')->cascadeOnDelete();
            $table->unsignedTinyInteger('dokumen_score')->default(0);
            $table->unsignedInteger('kurikulum')->default(0);
            $table->unsignedInteger('magang')->default(0);
            $table->unsignedInteger('dosen_industri')->default(0);
            $table->unsignedInteger('rekrutmen')->default(0);
            $table->decimal('penelitian_cash', 15, 2)->default(0);
            $table->decimal('penelitian_kind', 15, 2)->default(0);
            $table->unsignedInteger('hilirisasi')->default(0);
            $table->unsignedInteger('khalayak_pkm')->default(0);
            $table->unsignedInteger('publikasi_bersama')->default(0);
            $table->unsignedInteger('co_hosting')->default(0);
            $table->unsignedInteger('pelatihan_sertifikasi')->default(0);
            $table->decimal('kajian_tenaga_ahli', 15, 2)->default(0);
            $table->decimal('hibah_alat', 15, 2)->default(0);
            $table->unsignedInteger('reputasi')->default(0);
            $table->unsignedInteger('perluasan_jejaring')->default(0);
            $table->decimal('total_score', 8, 4)->default(0);
            $table->unsignedInteger('ranking')->nullable();
            $table->timestamps();

            $table->unique(['mitra_award_period_id', 'mitra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_award_scores');
    }
};
