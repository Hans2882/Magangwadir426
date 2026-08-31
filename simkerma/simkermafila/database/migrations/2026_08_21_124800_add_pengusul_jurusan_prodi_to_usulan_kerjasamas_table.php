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
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->string('pengusul_jurusan')->nullable();
            $table->string('pengusul_prodi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->dropColumn(['pengusul_jurusan', 'pengusul_prodi']);
        });
    }
};
