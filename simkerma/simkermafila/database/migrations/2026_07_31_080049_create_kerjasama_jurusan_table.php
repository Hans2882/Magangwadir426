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
        Schema::create('kerjasama_jurusan', function (Blueprint $table) {
            $table->id();
            $table->integer('kerjasama_id')->nullable();
            $table->unsignedBigInteger('jurusan_id')->nullable();

            $table->foreign('kerjasama_id')->references('id')->on('kerjasama')->onDelete('cascade');
            $table->foreign('jurusan_id')->references('id')->on('master_jurusans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kerjasama_jurusan');
    }
};
