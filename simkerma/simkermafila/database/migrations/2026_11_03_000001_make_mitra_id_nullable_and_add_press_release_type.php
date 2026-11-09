<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Make mitra_id nullable on kerjasama table
        DB::statement('ALTER TABLE `kerjasama` MODIFY COLUMN `mitra_id` int NULL;');

        // 2. Ensure master_jenis_dokumen has Press Release (ID: 9)
        DB::table('master_jenis_dokumen')->updateOrInsert(
            ['id' => 9],
            ['nama' => 'Press Release']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Press Release record if no kerjasama uses it
        $inUse = DB::table('kerjasama')->where('jenis_dokumen_id', 9)->exists();
        if (!$inUse) {
            DB::table('master_jenis_dokumen')->where('id', 9)->delete();
        }

        // Only revert column to NOT NULL if there are no null rows
        $hasNullMitra = DB::table('kerjasama')->whereNull('mitra_id')->exists();
        if (!$hasNullMitra) {
            DB::statement('ALTER TABLE `kerjasama` MODIFY COLUMN `mitra_id` int NOT NULL;');
        }
    }
};

