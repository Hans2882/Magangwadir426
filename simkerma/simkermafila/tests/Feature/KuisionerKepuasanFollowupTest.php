<?php

namespace Tests\Feature;

use App\Models\KuisionerKepuasan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class KuisionerKepuasanFollowupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('kuisioner_kepuasan')) {
            Schema::create('kuisioner_kepuasan', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('jabatan');
                $table->string('instansi');
                $table->string('email');
                $table->string('telepon');
                $table->string('nomor_dokumen')->nullable();
                $table->integer('komunikasi')->nullable();
                $table->integer('proses')->nullable();
                $table->integer('bantuan')->nullable();
                $table->integer('sdm_profesionalisme')->nullable();
                $table->integer('harapan')->nullable();
                $table->integer('manfaat')->nullable();
                $table->integer('kembali')->nullable();
                $table->integer('implementasi')->nullable();
                $table->integer('laporan')->nullable();
                $table->string('alumni_ada')->nullable();
                $table->integer('etika')->nullable();
                $table->integer('kepemimpinan')->nullable();
                $table->integer('etos_kerja')->nullable();
                $table->integer('komunikasi_alumni')->nullable();
                $table->integer('kerjasama_tim')->nullable();
                $table->integer('keahlian_bidang_ilmu')->nullable();
                $table->integer('keahlian_bidang_ilmu_terapan')->nullable();
                $table->integer('bahasa_asing')->nullable();
                $table->integer('teknologi_informasi')->nullable();
                $table->integer('pengembangan_diri')->nullable();
                $table->text('saran_kerjasama')->nullable();
                $table->text('saran_alumni')->nullable();
                $table->string('program_studi_alumni')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kuisioner_kepuasan_followup')) {
            Schema::create('kuisioner_kepuasan_followup', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kuisioner_kepuasan_id')->constrained('kuisioner_kepuasan')->cascadeOnDelete();
                $table->text('catatan');
                $table->string('status')->default('open');
                $table->timestamps();
            });
        }
    }

    public function test_kuisioner_can_store_and_track_followup_status(): void
    {
        $survey = KuisionerKepuasan::create([
            'nama' => 'Budi',
            'jabatan' => 'Manager',
            'instansi' => 'PT Demo',
            'email' => 'budi@example.com',
            'telepon' => '08123456789',
            'nomor_dokumen' => 'MOA-001',
            'komunikasi' => 5,
            'proses' => 4,
            'bantuan' => 3,
            'sdm_profesionalisme' => 4,
            'harapan' => 4,
            'manfaat' => 5,
            'kembali' => 3,
            'implementasi' => 4,
            'laporan' => 5,
            'alumni_ada' => 'Ya',
            'etika' => 4,
            'kepemimpinan' => 4,
            'etos_kerja' => 5,
            'komunikasi_alumni' => 4,
            'kerjasama_tim' => 5,
            'keahlian_bidang_ilmu' => 4,
            'keahlian_bidang_ilmu_terapan' => 5,
            'bahasa_asing' => 3,
            'teknologi_informasi' => 4,
            'pengembangan_diri' => 5,
            'saran_kerjasama' => 'Perlu komunikasi lebih cepat.',
            'saran_alumni' => 'Lebih aktif.',
            'program_studi_alumni' => 'Teknik Informatika',
        ]);

        $this->assertEquals('open', $survey->latest_status);

        $survey->tindakLanjut()->create([
            'catatan' => 'Menunggu jawaban dari tim mitra.',
            'status' => 'open',
        ]);

        $this->assertEquals('open', $survey->fresh()->latest_status);

        $survey->tindakLanjut()->create([
            'catatan' => 'Masalah sudah ditangani dan feedback sudah dikirim.',
            'status' => 'close',
        ]);

        $this->assertEquals('close', $survey->fresh()->latest_status);
    }
}
