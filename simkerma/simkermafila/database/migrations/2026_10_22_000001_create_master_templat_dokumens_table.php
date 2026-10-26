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
        Schema::create('master_templat_dokumens', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('file_name');
            $table->string('kategori')->default('Lainnya');
            $table->string('bahasa', 10)->default('ID');
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Seed 9 initial templates automatically
        $templates = [
            [
                'file_name' => 'Templat NK MoU Polinema Short_Indo.docx',
                'judul' => 'Nota Kesepahaman (MoU) Polinema - Short',
                'bahasa' => 'ID',
                'kategori' => 'MoU',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Templat NK MoU Polinema Standart_Indo.docx',
                'judul' => 'Nota Kesepahaman (MoU) Polinema - Standard',
                'bahasa' => 'ID',
                'kategori' => 'MoU',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template MoU Polinema Short_Eng.docx',
                'judul' => 'Memorandum of Understanding (MoU) - Short',
                'bahasa' => 'EN',
                'kategori' => 'MoU',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template MoU Polinema Standard_Eng.docx',
                'judul' => 'Memorandum of Understanding (MoU) - Standard',
                'bahasa' => 'EN',
                'kategori' => 'MoU',
                'urutan' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template_IA.docx',
                'judul' => 'Implementation Arrangement (IA)',
                'bahasa' => 'ID',
                'kategori' => 'IA',
                'urutan' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template_Laporan Pelaksanaan Kerja Sama.docx',
                'judul' => 'Laporan Pelaksanaan Kerja Sama',
                'bahasa' => 'ID',
                'kategori' => 'Laporan',
                'urutan' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template_Laporan Pelaksanaan Kerja Sama_ENG.docx',
                'judul' => 'Implementation of Cooperation Report',
                'bahasa' => 'EN',
                'kategori' => 'Laporan',
                'urutan' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template_PKS.docx',
                'judul' => 'Perjanjian Kerja Sama (PKS)',
                'bahasa' => 'ID',
                'kategori' => 'PKS',
                'urutan' => 8,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'file_name' => 'Template Laporan Collaborative Case Studies.docx',
                'judul' => 'Laporan Collaborative Case Studies',
                'bahasa' => 'ID',
                'kategori' => 'Case Study',
                'urutan' => 9,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('master_templat_dokumens')->insert($templates);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_templat_dokumens');
    }
};
