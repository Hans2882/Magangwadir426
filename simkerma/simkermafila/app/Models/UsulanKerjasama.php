<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsulanKerjasama extends Model
{
    protected $fillable = [
        'user_id',
        'mitra_id',
        'tipe_inisiasi',
        'dokumen_pendukung',
        'status_usulan',
        'keterangan',
        'usulan_nama_mitra',
        'usulan_kategori_id',
        'usulan_negara_id',
        'usulan_provinsi_id',
        'usulan_kota_id',
        'usulan_telepon',
        'usulan_email',
        'usulan_qs_rank',
        'usulan_alamat',
        'pengusul_nama',
        'pengusul_nip',
        'pengusul_jabatan',
        'pengusul_jurusan',
        'pengusul_prodi',
        'nomor_dokumen',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function kegiatans()
    {
        return $this->belongsToMany(MasterKegiatan::class, 'usulan_kegiatans', 'usulan_kerjasama_id', 'master_kegiatan_id');
    }

    public function usulanKategori()
    {
        return $this->belongsTo(MasterMitraIku::class, 'usulan_kategori_id');
    }

    public function usulanNegara()
    {
        return $this->belongsTo(Negara::class, 'usulan_negara_id');
    }

    public function usulanProvinsiModel()
    {
        return $this->belongsTo(MasterProvinsi::class, 'usulan_provinsi_id');
    }

    public function usulanKotaModel()
    {
        return $this->belongsTo(MasterKota::class, 'usulan_kota_id');
    }
}
