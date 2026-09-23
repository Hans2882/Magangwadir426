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
        Schema::create('permintaan_kerjasamas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mitra');
            $table->integer('kategori_id');
            $table->foreign('kategori_id')->references('id')->on('master_mitra_iku');
            $table->integer('negara_id')->nullable();
            $table->foreign('negara_id')->references('id')->on('master_negara');
            $table->string('qs_rank', 50)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota')->nullable(); // Legacy field
            $table->string('provinsi')->nullable(); // Legacy field
            $table->foreignId('provinsi_id')->nullable()->constrained('master_provinsi');
            $table->foreignId('kota_id')->nullable()->constrained('master_kota');
            $table->string('pic')->nullable();
            $table->string('status_permintaan')->default('Proses'); // Proses, Disetujui, Ditolak
            $table->foreignId('user_id')->nullable()->constrained('users'); // Pengusul internal
            $table->text('keterangan_tolak')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_kerjasamas');
    }
};
