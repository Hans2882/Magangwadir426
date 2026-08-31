<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuisioner_kepuasan_followup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuisioner_kepuasan_id')->constrained('kuisioner_kepuasan')->cascadeOnDelete();
            $table->text('catatan');
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuisioner_kepuasan_followup');
    }
};
