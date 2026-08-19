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
        Schema::create('usulan_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_kerjasama_id')->constrained()->cascadeOnDelete();
            $table->integer('master_kegiatan_id');
            $table->foreign('master_kegiatan_id')->references('id')->on('master_kegiatan')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usulan_kegiatans');
    }
};
