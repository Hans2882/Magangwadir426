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
            $table->string('pengusul_nama')->nullable();
            $table->string('pengusul_nip')->nullable();
            $table->string('pengusul_jabatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->dropColumn(['pengusul_nama', 'pengusul_nip', 'pengusul_jabatan']);
        });
    }
};
