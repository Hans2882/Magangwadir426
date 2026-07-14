<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KerjasamaExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function map($item): array
    {
        return [
            $item->jenisDokumen?->nama,
            $item->judul,
            $item->mitra?->nama_mitra,
            $item->jenis,
            $item->prodis->pluck('nama_prodi')->implode(', '),
            $item->bidang?->bidang_kerjasama,
            $item->nomor_dokumen,
            $item->tahun,
            optional($item->tanggal_awal)->format('d/m/Y'),
            optional($item->tanggal_akhir)->format('d/m/Y'),
            $item->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Jenis Dokumen',
            'Judul',
            'Nama Mitra',
            'Jenis Kerjasama',
            'Program Studi',
            'Bidang',
            'Nomor Dokumen',
            'Tahun',
            'Tanggal Awal',
            'Tanggal Akhir',
            'Status',
        ];
    }
}