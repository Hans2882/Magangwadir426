<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Privilege;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Privilege::firstOrCreate(
            ['nama' => 'Mahasiswa'],
            [
                'is_admin_panel' => false,
                'deskripsi' => 'Privilege khusus Mahasiswa (Dapat melihat & mengajukan Case Study serta Permintaan Kerjasama)',
                'can_create' => false,
                'can_read' => true,
                'can_update' => false,
                'can_delete' => false,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Privilege::where('nama', 'Mahasiswa')->delete();
    }
};
