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
            // Custom activities that are NOT part of the master_kegiatan list.
            // Stored on the proposal row only and used purely for PDF generation.
            $table->json('kegiatan_lainnya')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->dropColumn('kegiatan_lainnya');
        });
    }
};
