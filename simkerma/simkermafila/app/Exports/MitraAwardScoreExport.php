<?php

namespace App\Exports;

use App\Models\MitraAwardScore;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MitraAwardScoreExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected int $periodId
    ) {}

    public function query(): Builder
    {
        return MitraAwardScore::query()
            ->with([
                'mitra.kategori',
                'mitra.negara',
            ])
            ->where(
                'mitra_award_period_id',
                $this->periodId
            )
            ->orderBy('ranking');
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Nama Mitra',
            'Kategori IKU',
            'Negara',
            'Skor Dokumen',
            'Kurikulum',
            'Magang',
            'Dosen Industri',
            'Rekrutmen',
            'Penelitian In-Cash',
            'Penelitian In-Kind',
            'Hilirisasi',
            'Khalayak PkM',
            'Publikasi Bersama',
            'Co-hosting',
            'Pelatihan / Sertifikasi',
            'Kajian / Tenaga Ahli',
            'Hibah Alat',
            'Reputasi',
            'Perluasan Jejaring',
            'Total Score',
        ];
    }

    public function map($score): array
    {
        return [
            $score->ranking,
            $score->mitra?->nama_mitra ?? '-',
            $score->mitra?->kategori?->kategori ?? '-',
            $score->mitra?->negara?->nama_negara ?? 'Indonesia',

            $score->dokumen_score,

            $score->kurikulum,
            $score->magang,
            $score->dosen_industri,
            $score->rekrutmen,

            $score->penelitian_cash,
            $score->penelitian_kind,

            $score->hilirisasi,
            $score->khalayak_pkm,
            $score->publikasi_bersama,
            $score->co_hosting,

            $score->pelatihan_sertifikasi,
            $score->kajian_tenaga_ahli,
            $score->hibah_alat,

            $score->reputasi,
            $score->perluasan_jejaring,

            $score->total_score,
        ];
    }
}