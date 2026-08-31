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
        Schema::create('usulan_kerjasamas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('mitra_id');
            $table->foreign('mitra_id')->references('id')->on('mitra')->cascadeOnDelete();
            $table->string('tipe_inisiasi')->default('Bottom-Up');
            $table->string('dokumen_pendukung')->nullable();
            $table->string('status_usulan')->default('Menunggu Review'); // Menunggu Review, Direvisi, Disetujui, Ditolak
            $table->text('keterangan')->nullable(); // For rejection or revision notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usulan_kerjasamas');
    }
};
