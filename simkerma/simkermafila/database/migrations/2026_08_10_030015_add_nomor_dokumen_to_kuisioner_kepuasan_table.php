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
        Schema::table('kuisioner_kepuasan', function (Blueprint $table) {
            $table->string('nomor_dokumen')->nullable()->after('telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuisioner_kepuasan', function (Blueprint $table) {
            $table->dropColumn('nomor_dokumen');
        });
    }
};
