<?php

namespace App\Exports;

use App\Models\Mitra;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MitraExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths
{
    use Exportable;

    /**
     * Operational document type IDs that carry Jurusan & Program Studi relations.
     * (2 = MoA, 3 = PKS, 4 = IA, 5 = SPK, 6 = LoC)
     */
    private const OPERATIONAL_DOC_TYPE_IDS = [2, 3, 4, 5, 6];

    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Build the filtered Mitra query with minimal eager loading for export.
     */
    public function query(): Builder
    {
        return $this->query
            ->with([
                'kategori:id,kategori',
                'negara:id,nama_negara',
                'provinsiModel:id,nama_provinsi',
                'kotaModel:id,nama_kota',
                'kerjasamas' => fn ($q) => $q
                    ->select(['id', 'mitra_id', 'jenis_dokumen_id', 'tanggal_akhir'])
                    ->with([
                        'jurusans:id,nama_jurusan',
                        'prodis:id,jurusan_id,nama_prodi',
                        'prodis.jurusan:id,nama_jurusan',
                    ]),
            ])
            ->when(
                empty($this->query->getQuery()->orders),
                fn (Builder $q) => $q->orderBy('nama_mitra', 'asc')
            );
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'Nama Mitra',
            'Kategori Perusahaan',
            'Negara',
            'Provinsi',
            'Kota',
            'No. Telepon',
            'Email',
            'Alamat',
            'Jurusan',
            'Program Studi',
            'Status Mitra',
        ];
    }

    /**
     * Map a Mitra model into an Excel row.
     *
     * @param Mitra $item
     */
    public function map(mixed $item): array
    {
        $operationalDocs = $item->kerjasamas->filter(
            fn ($doc) => in_array((int) $doc->jenis_dokumen_id, self::OPERATIONAL_DOC_TYPE_IDS, true)
        );

        $jurusanText = $operationalDocs
            ->flatMap(function ($doc) {
                $directJurusans = $doc->jurusans->pluck('nama_jurusan');
                $prodiJurusans = $doc->prodis->map(fn ($prodi) => $prodi->jurusan?->nama_jurusan);

                return $directJurusans->merge($prodiJurusans);
            })
            ->filter(fn ($name) => filled($name))
            ->unique()
            ->values()
            ->implode(', ');

        $prodiText = $operationalDocs
            ->flatMap(fn ($doc) => $doc->prodis->pluck('nama_prodi'))
            ->filter(fn ($name) => filled($name))
            ->unique()
            ->values()
            ->implode(', ');

        $negara = ($item->negara_id && $item->negara_id >= 1)
            ? ($item->negara?->nama_negara ?? 'Indonesia')
            : 'Indonesia';

        $provinsi = $item->provinsiModel?->nama_provinsi ?: ($item->provinsi ?: '-');
        $kota = $item->kotaModel?->nama_kota ?: ($item->kota ?: '-');

        return [
            $item->nama_mitra ?: '-',
            $item->kategori?->kategori ?: '-',
            $negara,
            $provinsi,
            $kota,
            $item->telepon ?: '-',
            $item->email ?: '-',
            $item->alamat ?: '-',
            $jurusanText !== '' ? $jurusanText : '-',
            $prodiText !== '' ? $prodiText : '-',
            $this->resolveMitraStatus($item),
        ];
    }

    /**
     * Determine Mitra status from its MoU documents, matching ListMitras status_mitra filter.
     */
    private function resolveMitraStatus(Mitra $item): string
    {
        $mous = $item->kerjasamas->where('jenis_dokumen_id', 1);

        if ($mous->isEmpty()) {
            return 'BELUM ADA MOU';
        }

        $statuses = $mous->map(fn ($mou) => $mou->status)->all();

        if (in_array('AKTIF', $statuses, true)) {
            return 'AKTIF';
        }

        if (in_array('AKAN BERAKHIR', $statuses, true)) {
            return 'AKAN BERAKHIR';
        }

        return 'BERAKHIR';
    }

    /**
     * Apply formatting after the worksheet is generated.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }
            },
        ];
    }

    /**
     * Excel column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 38, // Nama Mitra
            'B' => 28, // Kategori Perusahaan
            'C' => 18, // Negara
            'D' => 22, // Provinsi
            'E' => 22, // Kota
            'F' => 18, // No. Telepon
            'G' => 28, // Email
            'H' => 40, // Alamat
            'I' => 32, // Jurusan
            'J' => 35, // Program Studi
            'K' => 18, // Status Mitra
        ];
    }
}
