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
        DB::statement($this->createViewSql());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_polinema_pelaporan_kegiatan;");
    }

    /**
     * SQL Definition for view_polinema_pelaporan_kegiatan.
     */
    private function createViewSql(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW view_polinema_pelaporan_kegiatan AS
SELECT 
    k.id AS id_kegiatan,
    k.nomor_dokumen AS nomor_kegiatan,
    k.judul AS judul_kegiatan,
    jd.nama AS kategori_kegiatan,
    k.jenis_dokumen_id AS id_kategori_kegiatan,
    COALESCE(k.tahun, YEAR(k.tanggal_awal), YEAR(k.created_at)) AS tahun_kegiatan,
    k.tanggal_awal AS tanggal_mulai,
    k.tanggal_akhir AS tanggal_selesai,
    k.status_workflow AS status_validasi,
    CASE WHEN k.status_workflow = 'Selesai' THEN 1 ELSE 0 END AS is_valid,

    -- Hubungan Dokumen Kerjasama Induk (MoU / PKS / IA)
    CASE WHEN k.parent_id IS NOT NULL AND p.id IS NOT NULL THEN 1 ELSE 0 END AS is_terhubung_kerjasama,
    k.parent_id AS id_dokumen_rujukan,
    p.nomor_dokumen AS nomor_dokumen_rujukan,
    p_jd.nama AS jenis_dokumen_rujukan,
    p.judul AS judul_dokumen_rujukan,
    CASE 
        WHEN k.parent_id IS NOT NULL AND p.id IS NOT NULL THEN 
            LOWER(TRIM(COALESCE(NULLIF(p.nomor_dokumen, ''), CONCAT('PARENT_ID_', k.parent_id))))
        ELSE NULL 
    END AS rujukan_deduplikasi_key,

    -- Mitra & Lingkup
    COALESCE(k.mitra_id, p.mitra_id) AS id_mitra,
    COALESCE(m.nama_mitra, p_m.nama_mitra, 'Mitra Belum Terdata') AS nama_mitra,
    COALESCE(m_iku.kategori, p_m_iku.kategori, '-') AS kategori_mitra,
    COALESCE(k.jenis, p.jenis, 'Dalam Negeri') AS jenis_kerjasama,

    -- Link Berita & Berkas Bukti
    k.link_dokumen AS link_dokumen,
    k.bukti_kegiatan AS link_bukti_kegiatan,

    -- Pengusul
    u.name AS nama_pengusul,
    u.email AS email_pengusul,

    -- Agregasi Jurusan & Kode Integrasi Polinema
    (
        SELECT GROUP_CONCAT(DISTINCT mj.nama_jurusan ORDER BY mj.nama_jurusan SEPARATOR ', ')
        FROM (
            SELECT kj.jurusan_id FROM kerjasama_jurusan kj WHERE kj.kerjasama_id = k.id
            UNION
            SELECT mps.jurusan_id FROM kerjasama_prodi kp JOIN master_program_studi mps ON kp.prodi_id = mps.id WHERE kp.kerjasama_id = k.id
        ) j_ids
        JOIN master_jurusans mj ON j_ids.jurusan_id = mj.id
    ) AS daftar_jurusan,

    (
        SELECT GROUP_CONCAT(DISTINCT mj.integrasi_jurusan ORDER BY mj.integrasi_jurusan SEPARATOR ',')
        FROM (
            SELECT kj.jurusan_id FROM kerjasama_jurusan kj WHERE kj.kerjasama_id = k.id
            UNION
            SELECT mps.jurusan_id FROM kerjasama_prodi kp JOIN master_program_studi mps ON kp.prodi_id = mps.id WHERE kp.kerjasama_id = k.id
        ) j_ids
        JOIN master_jurusans mj ON j_ids.jurusan_id = mj.id
    ) AS daftar_kode_integrasi_jurusan,

    -- Agregasi Prodi & Kode Integrasi Polinema
    (
        SELECT GROUP_CONCAT(DISTINCT mps.nama_prodi ORDER BY mps.nama_prodi SEPARATOR ', ')
        FROM kerjasama_prodi kp
        JOIN master_program_studi mps ON kp.prodi_id = mps.id
        WHERE kp.kerjasama_id = k.id
    ) AS daftar_prodi,

    (
        SELECT GROUP_CONCAT(DISTINCT mps.integrasi_prodi ORDER BY mps.integrasi_prodi SEPARATOR ',')
        FROM kerjasama_prodi kp
        JOIN master_program_studi mps ON kp.prodi_id = mps.id
        WHERE kp.kerjasama_id = k.id
    ) AS daftar_kode_integrasi_prodi,

    k.created_at,
    k.updated_at
FROM kerjasama k
JOIN master_jenis_dokumen jd ON k.jenis_dokumen_id = jd.id
LEFT JOIN kerjasama p ON k.parent_id = p.id
LEFT JOIN master_jenis_dokumen p_jd ON p.jenis_dokumen_id = p_jd.id
LEFT JOIN mitra m ON k.mitra_id = m.id
LEFT JOIN master_mitra_iku m_iku ON m.kategori_id = m_iku.id
LEFT JOIN mitra p_m ON p.mitra_id = p_m.id
LEFT JOIN master_mitra_iku p_m_iku ON p_m.kategori_id = p_m_iku.id
LEFT JOIN users u ON k.user_id = u.id
WHERE k.jenis_dokumen_id IN (8, 9);
SQL;
    }
};

