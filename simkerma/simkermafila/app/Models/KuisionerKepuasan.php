<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuisionerKepuasan extends Model
{
    protected $table = 'kuisioner_kepuasan';

    protected $fillable = [
        'nama',
        'jabatan',
        'instansi',
        'email',
        'telepon',
        'nomor_dokumen',
        'komunikasi',
        'proses',
        'bantuan',
        'sdm_profesionalisme',
        'harapan',
        'manfaat',
        'kembali',
        'implementasi',
        'laporan',
        'alumni_ada',
        'etika',
        'kepemimpinan',
        'etos_kerja',
        'komunikasi_alumni',
        'kerjasama_tim',
        'keahlian_bidang_ilmu',
        'keahlian_bidang_ilmu_terapan',
        'bahasa_asing',
        'teknologi_informasi',
        'pengembangan_diri',
        'saran_kerjasama',
        'saran_alumni',
        'program_studi_alumni',
    ];

    protected $casts = [
        'komunikasi' => 'integer',
        'proses' => 'integer',
        'bantuan' => 'integer',
        'sdm_profesionalisme' => 'integer',
        'harapan' => 'integer',
        'manfaat' => 'integer',
        'kembali' => 'integer',
        'implementasi' => 'integer',
        'laporan' => 'integer',
        'etika' => 'integer',
        'kepemimpinan' => 'integer',
        'etos_kerja' => 'integer',
        'komunikasi_alumni' => 'integer',
        'kerjasama_tim' => 'integer',
        'keahlian_bidang_ilmu' => 'integer',
        'keahlian_bidang_ilmu_terapan' => 'integer',
        'bahasa_asing' => 'integer',
        'teknologi_informasi' => 'integer',
        'pengembangan_diri' => 'integer',
    ];
}
