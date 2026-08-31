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
        Schema::table('mitra', function (Blueprint $table) {
            $table->foreignId('provinsi_id')->nullable()->constrained('master_provinsi')->nullOnDelete();
            $table->foreignId('kota_id')->nullable()->constrained('master_kota')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitra', function (Blueprint $table) {
            $table->dropForeign(['provinsi_id']);
            $table->dropColumn('provinsi_id');
            $table->dropForeign(['kota_id']);
            $table->dropColumn('kota_id');
        });
    }
};
