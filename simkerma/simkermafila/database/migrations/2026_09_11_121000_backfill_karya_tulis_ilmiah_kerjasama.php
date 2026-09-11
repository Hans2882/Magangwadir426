<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kerjasama')
            ->whereIn('jenis_dokumen_id', [9, 10, 11])
            ->whereNotNull('kerjasama_id')
            ->select(['id', 'kerjasama_id'])
            ->orderBy('id')
            ->chunkById(500, function ($records): void {
                $rows = $records->map(fn ($record) => [
                    'karya_tulis_ilmiah_id' => $record->id,
                    'kerjasama_id' => $record->kerjasama_id,
                ])->all();

                DB::table('karya_tulis_ilmiah_kerjasama')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        DB::table('karya_tulis_ilmiah_kerjasama')
            ->whereIn('karya_tulis_ilmiah_id', function ($query) {
                return $query->select('id')->from('kerjasama')->whereIn('jenis_dokumen_id', [9, 10, 11]);
            })
            ->delete();
    }
};