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
        Schema::table('permintaan_kerjasamas', function (Blueprint $table) {
            $table->string('nama_pengusul')->nullable()->after('user_id')->comment('Nama pengusul dari sistem luar (misal: API SIMMAGANG)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permintaan_kerjasamas', function (Blueprint $table) {
            $table->dropColumn('nama_pengusul');
        });
    }
};
