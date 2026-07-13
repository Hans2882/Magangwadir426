<?php

namespace App\Exports;

use App\Models\Kerjasama;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KerjasamaExport implements FromCollection, WithHeadings
{
    protected array $jenisDokumen;
    protected ?string $jenis;

    public function __construct(array $jenisDokumen, ?string $jenis = null)
    {
        $this->jenisDokumen = $jenisDokumen;
        $this->jenis = $jenis;
    }

    public function collection(): Collection
    {
        $query = Kerjasama::with([
            'mitra',
            'bidang',
            'prodis',
            'jenisDokumen',
        ])->whereIn('jenis_dokumen_id', $this->jenisDokumen);

        if ($this->jenis) {
            $query->where('jenis', $this->jenis);
        }

        return $query->get()->map(function ($item) {

            return [

                'Jenis Dokumen' =>
                    $item->jenisDokumen?->nama,

                'Judul' =>
                    $item->judul,

                'Nama Mitra' =>
                    $item->mitra?->nama_mitra,

                'Jenis Kerjasama' =>
                    $item->jenis,

                'Program Studi' =>
                    $item->prodis
                        ->pluck('nama_prodi')
                        ->implode(', '),

                'Bidang' =>
                    $item->bidang?->bidang_kerjasama,

                'Nomor Dokumen' =>
                    $item->nomor_dokumen,

                'Tahun' =>
                    $item->tahun,

                'Tanggal Awal' =>
                    optional($item->tanggal_awal)
                        ->format('d/m/Y'),

                'Tanggal Akhir' =>
                    optional($item->tanggal_akhir)
                        ->format('d/m/Y'),

                'Status' =>
                    $item->status,
            ];
        });
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