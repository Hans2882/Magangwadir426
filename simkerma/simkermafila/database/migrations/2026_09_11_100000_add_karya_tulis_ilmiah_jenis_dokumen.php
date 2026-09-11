<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('master_jenis_dokumen')->insert([
            ['id' => 9, 'nama' => 'Jurnal Ilmiah'],
            ['id' => 10, 'nama' => 'Karya Rujukan'],
            ['id' => 11, 'nama' => 'Laporan Penelitian'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('master_jenis_dokumen')->whereIn('id', [9, 10, 11])->delete();
    }
};
