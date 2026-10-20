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
        // 1. Privilege Dosen
        Privilege::firstOrCreate(
            ['nama' => 'Dosen'],
            [
                'is_admin_panel' => false,
                'deskripsi' => 'Pengguna Dosen / Tenaga Pendidik Polinema',
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => false,
            ]
        );

        // 2. Privilege Wakil Direktur IV
        Privilege::firstOrCreate(
            ['nama' => 'Wakil Direktur IV'],
            [
                'is_admin_panel' => true,
                'deskripsi' => 'Pejabat Wakil Direktur IV (Bidang Perencanaan dan Kerja Sama)',
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => false,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Privilege::whereIn('nama', ['Dosen', 'Wakil Direktur IV'])->delete();
    }
};
