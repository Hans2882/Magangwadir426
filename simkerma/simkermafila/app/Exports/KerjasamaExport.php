<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class KerjasamaExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths
{
    use Exportable;

    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query(): Builder
    {
        return $this->query->with([
            'mitra',
            'bidang',
            'prodis',
            'jurusans',
            'jenisDokumen',
        ]);
    }

    /**
     * Mapping data untuk setiap baris Excel.
     *
     * @param \App\Models\Kerjasama $item
     */
    public function map(mixed $item): array
    {
        /*
         * ============================================================
         * LINK DOKUMEN
         * ============================================================
         */

        $link = '';

        $documentPath = (string) ($item->link_dokumen ?? '');

        if ($documentPath !== '' && $documentPath !== '-') {
            try {
                /*
                 * Jika sudah berupa URL, gunakan langsung.
                 */
                if (str_starts_with($documentPath, 'http')) {
                    $link = $documentPath;
                } else {
                    /*
                     * Jika berupa path Google Drive,
                     * ubah menjadi URL view Google Drive.
                     */
                    /** @var FilesystemAdapter $googleDisk */
                    $googleDisk = Storage::disk('google');

                    /** @var \Masbug\Flysystem\GoogleDriveAdapter $googleDriveAdapter */
                    $googleDriveAdapter = $googleDisk->getAdapter();

                    $driveUrl = (string) $googleDriveAdapter->getUrl(
                        $documentPath
                    );

                    $queryString = parse_url(
                        $driveUrl,
                        PHP_URL_QUERY
                    );

                    parse_str(
                        is_string($queryString)
                            ? $queryString
                            : '',
                        $query
                    );

                    if (!empty($query['id'])) {
                        $link = "https://drive.google.com/file/d/{$query['id']}/view";
                    }
                }
            } catch (\Throwable $e) {
                /*
                 * Jika gagal mendapatkan URL,
                 * export tetap dilanjutkan tanpa link.
                 */
                $link = '';
            }
        }

        /*
         * ============================================================
         * JENIS DOKUMEN
         * ============================================================
         */

        $jenisDokumen = strtoupper(
            trim((string) ($item->jenisDokumen?->nama ?? ''))
        );

        /*
         * ============================================================
         * PROGRAM STUDI & JURUSAN (DEDUPLIKASI)
         * ============================================================
         *
         * MoU:
         *   Tidak menggunakan Program Studi maupun Jurusan (-).
         *
         * PKS, MoA, IA, SPK, LoC, dll:
         *   Menggunakan Program Studi dan Jurusan (bebas duplikat).
         */

        $prodiText = '';
        $jurusanText = '';

        if ($jenisDokumen !== 'MOU') {
            $prodiText = $item->prodis
                ?->pluck('nama_prodi')
                ?->unique()
                ?->filter()
                ?->values()
                ?->implode(', ') ?? '';

            $jurusanText = $item->jurusans
                ?->pluck('nama_jurusan')
                ?->unique()
                ?->filter()
                ?->values()
                ?->implode(', ') ?? '';
        }

        /*
         * ============================================================
         * STANDAR 13 KOLOM TETAP (ZERO SHIFTING)
         * ============================================================
         */

        return [
            $jenisDokumen !== '' ? $jenisDokumen : '-',
            $item->judul ?? '-',
            $item->mitra?->nama_mitra ?? '-',
            $item->jenis ?? '-',
            $prodiText !== '' ? $prodiText : '-',
            $jurusanText !== '' ? $jurusanText : '-',
            $item->bidang?->bidang_kerjasama ?? '-',
            $item->nomor_dokumen ?? '-',
            $item->tahun ?? '-',
            optional($item->tanggal_awal)->format('d/m/Y') ?? '-',
            optional($item->tanggal_akhir)->format('d/m/Y') ?? '-',
            $item->status ?? '-',
            $link,
        ];
    }

    /**
     * Heading Excel (13 Kolom Tetap).
     */
    public function headings(): array
    {
        return [
            'Jenis Dokumen',
            'Judul',
            'Nama Mitra',
            'Jenis Kerjasama',
            'Program Studi',
            'Jurusan',
            'Bidang',
            'Nomor Dokumen',
            'Tahun',
            'Tanggal Awal',
            'Tanggal Akhir',
            'Status',
            'Dokumen',
        ];
    }

    /**
     * Event setelah sheet selesai dibuat.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                /*
                 * ====================================================
                 * CARI KOLOM DOKUMEN
                 * ====================================================
                 */

                $documentColumn = null;

                foreach (
                    $sheet->rangeToArray(
                        "A1:{$highestColumn}1",
                        null,
                        true,
                        false
                    )[0] as $index => $heading
                ) {
                    if ($heading === 'Dokumen') {

                        $documentColumn =
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                                $index + 1
                            );

                        break;
                    }
                }

                /*
                 * ====================================================
                 * HYPERLINK "LIHAT PDF"
                 * ====================================================
                 */

                if ($documentColumn) {

                    for ($row = 2; $row <= $highestRow; $row++) {

                        $url = $sheet
                            ->getCell("{$documentColumn}{$row}")
                            ->getValue();

                        if (is_string($url) && str_starts_with($url, 'http')) {

                            /*
                             * Ubah isi cell menjadi "Lihat PDF".
                             */
                            $sheet->setCellValue(
                                "{$documentColumn}{$row}",
                                'Lihat PDF'
                            );

                            /*
                             * Tambahkan hyperlink.
                             */
                            $sheet
                                ->getCell("{$documentColumn}{$row}")
                                ->getHyperlink()
                                ->setUrl($url);

                            /*
                             * Underline.
                             */
                            $sheet
                                ->getStyle("{$documentColumn}{$row}")
                                ->getFont()
                                ->setUnderline(true);

                            /*
                             * Warna hyperlink.
                             */
                            $sheet
                                ->getStyle("{$documentColumn}{$row}")
                                ->getFont()
                                ->getColor()
                                ->setARGB('FF0000FF');
                        } elseif (empty($url) || $url === '-') {
                            $sheet->setCellValue(
                                "{$documentColumn}{$row}",
                                '-'
                            );
                        }
                    }
                }

                /*
                 * ====================================================
                 * WRAP TEXT
                 * ====================================================
                 */

                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setWrapText(true);

                /*
                 * ====================================================
                 * VERTICAL ALIGN TOP
                 * ====================================================
                 */

                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_TOP
                    );

                /*
                 * ====================================================
                 * AUTO HEIGHT ROW
                 * ====================================================
                 */

                for ($row = 2; $row <= $highestRow; $row++) {

                    $sheet
                        ->getRowDimension($row)
                        ->setRowHeight(-1);
                }
            },
        ];
    }

    /**
     * Lebar kolom Excel.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20, // Jenis Dokumen
            'B' => 50, // Judul
            'C' => 35, // Nama Mitra
            'D' => 18, // Jenis Kerjasama
            'E' => 30, // Program Studi
            'F' => 30, // Jurusan
            'G' => 25, // Bidang
            'H' => 35, // Nomor Dokumen
            'I' => 10, // Tahun
            'J' => 15, // Tanggal Awal
            'K' => 15, // Tanggal Akhir
            'L' => 15, // Status
            'M' => 15, // Dokumen
        ];
    }
}