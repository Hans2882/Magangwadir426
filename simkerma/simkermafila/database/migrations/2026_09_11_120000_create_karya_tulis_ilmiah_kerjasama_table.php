<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karya_tulis_ilmiah_kerjasama', function (Blueprint $table): void {
            $table->integer('karya_tulis_ilmiah_id');
            $table->integer('kerjasama_id');
            $table->primary(['karya_tulis_ilmiah_id', 'kerjasama_id']);
            $table->foreign('karya_tulis_ilmiah_id')->references('id')->on('kerjasama')->cascadeOnDelete();
            $table->foreign('kerjasama_id')->references('id')->on('kerjasama')->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('karya_tulis_ilmiah_kerjasama');
    }
};