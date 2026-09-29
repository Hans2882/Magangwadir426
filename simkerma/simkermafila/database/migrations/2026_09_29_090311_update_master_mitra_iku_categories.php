<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Map existing old IDs to the new ones in related tables
        $mapping = [
            5 => 2,
            6 => 3,
            7 => 2,
            8 => 2,
            9 => 4,
            10 => 4,
            11 => 4,
            12 => 1,
            13 => 1,
            14 => 1,
            15 => 1,
            16 => 2,
        ];
        
        foreach ($mapping as $oldId => $newId) {
            DB::table('mitra')->where('kategori_id', $oldId)->update(['kategori_id' => $newId]);
            DB::table('usulan_kerjasamas')->where('usulan_kategori_id', $oldId)->update(['usulan_kategori_id' => $newId]);
            DB::table('permintaan_kerjasamas')->where('kategori_id', $oldId)->update(['kategori_id' => $newId]);
        }

        // 2. Update names of IDs 1 to 4
        DB::table('master_mitra_iku')->where('id', 1)->update(['kategori' => 'perusahaan swasta']);
        DB::table('master_mitra_iku')->where('id', 2)->update(['kategori' => 'lembaga/organisasi nirlaba']);
        DB::table('master_mitra_iku')->where('id', 3)->update(['kategori' => 'institusi/organisasi multilateral']);
        DB::table('master_mitra_iku')->where('id', 4)->update(['kategori' => 'instansi Pemerintah, BUMN, atau BUMD.']);

        // 3. Delete old categories (IDs 5-16)
        DB::table('master_mitra_iku')->whereIn('id', array_keys($mapping))->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible without losing context, but we could re-insert if needed.
    }
};
