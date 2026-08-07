<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuisioner_kepuasan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('instansi');
            $table->tinyInteger('rating');
            $table->text('pesan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuisioner_kepuasan');
    }
};
