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
        DB::table('kerjasama')
            ->where(function ($query) {
                $query->where('status_workflow', '!=', 'Selesai')
                      ->orWhereNull('status_workflow');
            })
            ->update(['status_workflow' => 'Selesai']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu me-revert status dokumen
    }
};
