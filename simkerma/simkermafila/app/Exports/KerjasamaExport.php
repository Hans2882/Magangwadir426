<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
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

    /**
     * @param \App\Models\Kerjasama $item
     */
    public function map(mixed $item): array
    {
        $link = '';
        $documentPath = (string) ($item->link_dokumen ?? '');

        if ($documentPath !== '' && $documentPath !== '-') {
            try {
                if (str_starts_with($documentPath, 'http')) {
                    // Sudah URL, gunakan apa adanya
                    $link = $documentPath;
                } else {
                    // Path Google Drive -> ubah menjadi URL view
                    /** @var FilesystemAdapter $googleDisk */
                    $googleDisk = Storage::disk('google');

                    /** @var \Masbug\Flysystem\GoogleDriveAdapter $googleDriveAdapter */
                    $googleDriveAdapter = $googleDisk->getAdapter();

                    $driveUrl = (string) $googleDriveAdapter->getUrl($documentPath);

                    $queryString = parse_url($driveUrl, PHP_URL_QUERY);

                    parse_str(
                        is_string($queryString) ? $queryString : '',
                        $query
                    );

                    if (!empty($query['id'])) {
                        $link = "https://drive.google.com/file/d/{$query['id']}/view";
                    }
                }
            } catch (\Throwable $e) {
                $link = '';
            }
        }

        $jenisDokumen = strtoupper(
            trim((string) ($item->jenisDokumen?->nama ?? ''))
        );

        /*
         * MOA / MOU / PKS / IA
         *
         * MoU  -> tidak menampilkan Program Studi
         * PKS  -> menampilkan Jurusan
         * IA   -> menampilkan Jurusan
         *
         * Dokumen lainnya -> tetap menggunakan Program Studi
         */
        $data = [
            $jenisDokumen,
            $item->judul,
            $item->mitra?->nama_mitra,
            $item->jenis,
        ];

        if ($jenisDokumen === 'MOU' || $jenisDokumen === 'MoU') {
            // MoU tidak menggunakan Program Studi
        } elseif (in_array($jenisDokumen, ['PKS', 'IA'])) {
            // PKS dan IA menggunakan Jurusan
            $data[] = $item->jurusans
                ?->pluck('nama_jurusan')
                ?->implode(', ') ?? '';
        } else {
            // Dokumen lainnya tetap menggunakan Program Studi
            $data[] = $item->prodis
                ?->pluck('nama_prodi')
                ?->implode(', ') ?? '';
        }

        $data[] = $item->bidang?->bidang_kerjasama;
        $data[] = $item->nomor_dokumen;
        $data[] = $item->tahun;
        $data[] = optional($item->tanggal_awal)->format('d/m/Y');
        $data[] = optional($item->tanggal_akhir)->format('d/m/Y');
        $data[] = $item->status;
        $data[] = $link;

        return $data;
    }

    public function headings(): array
    {
        /*
         * Karena headings() tidak menerima $item,
         * kita buat heading berdasarkan query yang akan diexport.
         *
         * Jika export hanya berisi satu jenis dokumen,
         * heading dapat dibuat sesuai jenis dokumen tersebut.
         */
        $jenisDokumen = $this->getJenisDokumenExport();

        $headings = [
            'Jenis Dokumen',
            'Judul',
            'Nama Mitra',
            'Jenis Kerjasama',
        ];

        if ($jenisDokumen === 'MOU') {
            // MoU -> tidak ada Program Studi / Jurusan
        } elseif (in_array($jenisDokumen, ['PKS', 'IA'])) {
            // PKS & IA -> Jurusan
            $headings[] = 'Jurusan';
        } else {
            // Dokumen lain -> Program Studi
            $headings[] = 'Program Studi';
        }

        $headings[] = 'Bidang';
        $headings[] = 'Nomor Dokumen';
        $headings[] = 'Tahun';
        $headings[] = 'Tanggal Awal';
        $headings[] = 'Tanggal Akhir';
        $headings[] = 'Status';
        $headings[] = 'Dokumen';

        return $headings;
    }

    /**
     * Mengambil jenis dokumen dari query export.
     *
     * Jika export hanya berisi satu jenis dokumen,
     * maka heading bisa dibuat secara dinamis.
     */
    protected function getJenisDokumenExport(): ?string
    {
        $model = clone $this->query;

        $item = $model
            ->with('jenisDokumen')
            ->first();

        if (!$item) {
            return null;
        }

        return strtoupper(
            trim((string) ($item->jenisDokumen?->nama ?? ''))
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Cari kolom Dokumen berdasarkan heading
                $documentColumn = null;

                foreach ($sheet->rangeToArray(
                    "A1:{$highestColumn}1",
                    null,
                    true,
                    false
                )[0] as $index => $heading) {

                    if ($heading === 'Dokumen') {
                        $documentColumn =
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                                $index + 1
                            );

                        break;
                    }
                }

                // Hyperlink "Lihat PDF"
                if ($documentColumn) {
                    for ($row = 2; $row <= $highestRow; $row++) {

                        $url = $sheet
                            ->getCell("{$documentColumn}{$row}")
                            ->getValue();

                        if (is_string($url) && $url !== '') {

                            $sheet->setCellValue(
                                "{$documentColumn}{$row}",
                                "Lihat PDF"
                            );

                            $sheet
                                ->getCell("{$documentColumn}{$row}")
                                ->getHyperlink()
                                ->setUrl($url);

                            $sheet
                                ->getStyle("{$documentColumn}{$row}")
                                ->getFont()
                                ->setUnderline(true);

                            $sheet
                                ->getStyle("{$documentColumn}{$row}")
                                ->getFont()
                                ->getColor()
                                ->setARGB('FF0000FF');
                        }
                    }
                }

                // Wrap text semua kolom
                $sheet
                    ->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // Vertical align top
                $sheet
                    ->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                // Tinggi baris otomatis
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet
                        ->getRowDimension($row)
                        ->setRowHeight(-1);
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Jenis Dokumen
            'B' => 50, // Judul
            'C' => 35, // Nama Mitra
            'D' => 18, // Jenis Kerjasama
            'E' => 30, // Prodi / Jurusan
            'F' => 25, // Bidang
            'G' => 35, // Nomor Dokumen
            'H' => 10, // Tahun
            'I' => 15, // Tanggal Awal
            'J' => 15, // Tanggal Akhir
            'K' => 15, // Status
            'L' => 15, // Dokumen
        ];
    }
}