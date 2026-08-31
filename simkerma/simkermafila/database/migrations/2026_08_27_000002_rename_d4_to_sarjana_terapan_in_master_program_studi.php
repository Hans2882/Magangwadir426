<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename the "D4 ..." prefix to "Sarjana Terapan ..." in master_program_studi.
     * Only the Indonesian display column (nama_prodi) is changed. Prefix-only
     * replacement so the rest of the program name is untouched.
     */
    public function up(): void
    {
        DB::table('master_program_studi')
            ->where('nama_prodi', 'LIKE', 'D4 %')
            ->update([
                'nama_prodi' => DB::raw("CONCAT('Sarjana Terapan ', SUBSTRING(nama_prodi, 4))"),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * Revert "Sarjana Terapan ..." back to "D4 ...". Safe here because no
     * pre-existing "Sarjana Terapan" rows exist (applied masters use "S2 Terapan").
     */
    public function down(): void
    {
        DB::table('master_program_studi')
            ->where('nama_prodi', 'LIKE', 'Sarjana Terapan %')
            ->update([
                'nama_prodi' => DB::raw("CONCAT('D4 ', SUBSTRING(nama_prodi, 17))"),
            ]);
    }
};
