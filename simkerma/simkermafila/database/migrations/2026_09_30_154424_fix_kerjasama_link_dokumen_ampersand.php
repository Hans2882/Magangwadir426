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
        \Illuminate\Support\Facades\DB::table('kerjasama')
            ->where('link_dokumen', 'LIKE', '%&%')
            ->get()
            ->each(function ($record) {
                \Illuminate\Support\Facades\DB::table('kerjasama')
                    ->where('id', $record->id)
                    ->update([
                        'link_dokumen' => str_replace('&', '_', $record->link_dokumen)
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
