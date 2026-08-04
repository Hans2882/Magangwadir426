<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Illuminate\Support\Facades\Storage;

class KerjasamaExport implements FromQuery, WithHeadings, WithMapping, WithEvents, WithColumnWidths
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
    $link = '';

if (!empty($item->link_dokumen) && $item->link_dokumen !== '-') {
    try {
        if (str_starts_with($item->link_dokumen, 'http')) {
            // Sudah URL, gunakan apa adanya
            $link = $item->link_dokumen;
        } else {
            // Path Google Drive -> ubah menjadi URL view
            $url = Storage::disk('google')->url($item->link_dokumen);

            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            if (!empty($query['id'])) {
                $link = "https://drive.google.com/file/d/{$query['id']}/view";
            }
        }
    } catch (\Throwable $e) {
        $link = '';
    }
}

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
        $link,
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
        'Dokumen',
    ];
}

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            $highestRow = $sheet->getHighestRow();

            // Hyperlink "Lihat PDF"
            for ($row = 2; $row <= $highestRow; $row++) {

                $url = $sheet->getCell("L{$row}")->getValue();

                if (!empty($url)) {

                    $sheet->setCellValue("L{$row}", "Lihat PDF");

                    $sheet->getCell("L{$row}")
                        ->getHyperlink()
                        ->setUrl($url);

                    $sheet->getStyle("L{$row}")
                        ->getFont()
                        ->setUnderline(true);

                    $sheet->getStyle("L{$row}")
                        ->getFont()
                        ->getColor()
                        ->setARGB('FF0000FF');
                }
            }

            // Wrap text semua kolom
            $sheet->getStyle('A1:L' . $highestRow)
                ->getAlignment()
                ->setWrapText(true);

            // Vertical align top
            $sheet->getStyle('A1:L' . $highestRow)
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP);

            // Tinggi baris otomatis
            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(-1);
            }
        },
    ];
}

public function columnWidths(): array
{
    return [
        'A' => 20,
        'B' => 50, // Judul
        'C' => 35,
        'D' => 18,
        'E' => 30,
        'F' => 25,
        'G' => 35,
        'H' => 10,
        'I' => 15,
        'J' => 15,
        'K' => 15,
        'L' => 15,
    ];
}
}