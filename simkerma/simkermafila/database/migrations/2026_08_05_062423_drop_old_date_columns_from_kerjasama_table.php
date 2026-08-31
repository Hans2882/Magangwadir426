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
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->dropColumn(['tanggal_awal_old_backup', 'tanggal_akhir_old_backup']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->string('tanggal_awal_old_backup')->nullable();
            $table->string('tanggal_akhir_old_backup')->nullable();
        });
    }
};
