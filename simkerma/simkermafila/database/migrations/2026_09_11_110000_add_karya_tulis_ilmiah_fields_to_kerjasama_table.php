<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kerjasama', function (Blueprint $table): void {
            $table->string('nama_jurnal')->nullable();
            $table->string('volume')->nullable();
            $table->string('issue')->nullable();
            $table->date('tanggal_publikasi')->nullable();
            $table->string('link_doi', 500)->nullable();
            $table->string('judul_karya')->nullable();
            $table->string('penulis')->nullable();
            $table->string('jenis_karya')->nullable();
            $table->string('penerbit')->nullable();
            $table->string('isbn_issn')->nullable();
            $table->unsignedSmallInteger('tahun_terbit')->nullable();
            $table->string('judul_penelitian')->nullable();
            $table->string('peneliti')->nullable();
            $table->string('mitra')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('ringkasan')->nullable();
            $table->string('link_laporan', 500)->nullable();
            $table->integer('kerjasama_id')->nullable();
            $table->foreign('kerjasama_id')->references('id')->on('kerjasama')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kerjasama', function (Blueprint $table): void {
            $table->dropForeign(['kerjasama_id']);
            $table->dropColumn([
                'nama_jurnal', 'volume', 'issue', 'tanggal_publikasi', 'link_doi',
                'judul_karya', 'penulis', 'jenis_karya', 'penerbit', 'isbn_issn', 'tahun_terbit',
                'judul_penelitian', 'peneliti', 'mitra', 'tahun', 'tanggal', 'ringkasan', 'link_laporan',
                'kerjasama_id',
            ]);
        });
    }
};