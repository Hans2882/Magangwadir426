<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class MouPdfAutofillService
{
    private const MAX_PDF_SIZE = 20 * 1024 * 1024;

    /** Batas halaman OCR (judul + nomor + tanggal biasanya di 1–3 halaman pertama) */
    private const MAX_OCR_PAGES = 3;

    /** Field kritis; kalau salah satu kosong, coba OCR */
    private const CRITICAL_FIELDS = ['judul', 'nomor_dokumen_polinema', 'tanggal_awal'];

    /** @param resource $stream */
    public function extractFromStream($stream): array
    {
        if (! is_resource($stream)) {
            throw new RuntimeException('File PDF tidak dapat dibuka dari penyimpanan.');
        }

        $path = tempnam(sys_get_temp_dir(), 'mou-pdf-');
        if ($path === false) {
            fclose($stream);
            throw new RuntimeException('File sementara untuk membaca PDF tidak dapat dibuat.');
        }

        try {
            $target = fopen($path, 'wb');
            if ($target === false) {
                throw new RuntimeException('File sementara untuk membaca PDF tidak dapat dibuka.');
            }

            $bytesCopied = false;
            try {
                $bytesCopied = stream_copy_to_stream($stream, $target, self::MAX_PDF_SIZE + 1);
            } finally {
                fclose($target);
            }

            if ($bytesCopied === false) {
                throw new RuntimeException('File PDF gagal diunduh dari penyimpanan.');
            }

            return $this->extractFromPdfPath($path);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function extractFromPdfPath(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File PDF tidak dapat dibaca.');
        }

        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Ukuran file PDF tidak dapat diperiksa.');
        }

        if ($size > self::MAX_PDF_SIZE) {
            throw new RuntimeException('Ukuran PDF melebihi batas 20 MB untuk pembacaan otomatis.');
        }

        if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('File yang dipilih bukan PDF yang valid.');
        }

        // Deteksi PDF terenkripsi / 0 halaman sebelum render
        $this->assertPdfIsProcessable($path);

        // 1. Coba pdftotext (PDF digital). Kalau gagal, jangan stop — tetap coba OCR.
        $text = '';
        try {
            $text = trim($this->runProcess([
                config('services.pdf_text_extractor.binary', 'pdftotext'),
                '-layout',
                '-nopgbrk',
                $path,
                '-',
            ], 20, 'Pembacaan teks PDF', 'Pastikan pdftotext terpasang dan PDFTOTEXT_BINARY sudah benar.')->getOutput());
        } catch (RuntimeException $e) {
            \Illuminate\Support\Facades\Log::warning('pdftotext gagal, lanjut ke OCR', [
                'error' => $e->getMessage(),
            ]);
        }

        $data = $this->extractFields($text);

        // 2. Kalau field kritis belum lengkap, coba OCR lalu gabungkan
        if ($this->needsOcr($text, $data)) {
            try {
                $ocrText = trim($this->extractScannedPdfText($path));
            } catch (RuntimeException $e) {
                if ($text === '') {
                    throw $e;
                }
                \Illuminate\Support\Facades\Log::warning('OCR gagal, memakai hasil pdftotext parsial', [
                    'error' => $e->getMessage(),
                ]);
                $ocrText = '';
            }

            if ($ocrText !== '') {
                $ocrData = $this->extractFields($ocrText);
                foreach ($ocrData as $key => $value) {
                    if (($value ?? null) !== null && $value !== ''
                        && (($data[$key] ?? null) === null || $data[$key] === '')) {
                        $data[$key] = $value;
                    }
                }
            } elseif ($text === '') {
                throw new RuntimeException('Teks tidak ditemukan pada PDF, termasuk setelah OCR. Pastikan hasil scan cukup jelas.');
            }
        }

        return $data;
    }

    private function needsOcr(string $text, array $data): bool
    {
        if ($text === '') {
            return true;
        }
        foreach (self::CRITICAL_FIELDS as $field) {
            if (($data[$field] ?? null) === null || $data[$field] === '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek dengan pdfinfo agar kita tahu PDF terenkripsi / 0 halaman sebelum render.
     * Kalau pdfinfo tidak tersedia, lewati saja (jangan block).
     */
    private function assertPdfIsProcessable(string $path): void
    {
        $binary = (string) config('services.pdf_ocr.pdfinfo_binary', 'pdfinfo');
        if ($binary === '') {
            return;
        }

        $process = new Process([$binary, $path]);
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return; // pdfinfo tidak ada → biarkan
        }

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        if (! $process->isSuccessful()) {
            $blob = $stdout . "\n" . $stderr;
            if (preg_match('/password|encrypted|encryption/i', $blob)) {
                throw new RuntimeException('PDF dilindungi kata sandi / terenkripsi. Hapus proteksi terlebih dahulu sebelum diunggah.');
            }
            return;
        }

        if (preg_match('/^Encrypted:\s+yes/im', $stdout)) {
            throw new RuntimeException('PDF terenkripsi. Hapus proteksi terlebih dahulu sebelum diunggah.');
        }
        if (preg_match('/^Pages:\s+0\s*$/im', $stdout)) {
            throw new RuntimeException('PDF tidak memiliki halaman yang bisa diproses.');
        }
    }

    private function extractScannedPdfText(string $path): string
    {
        $directory = $this->createTempDirectory();

        try {
            $prefix = $directory . DIRECTORY_SEPARATOR . 'page';
            $dpi = max(100, min(300, (int) config('services.pdf_ocr.dpi', 200)));

            $this->runProcess([
                config('services.pdf_ocr.renderer_binary', 'pdftoppm'),
                '-f', '1',
                '-l', (string) self::MAX_OCR_PAGES,
                '-r', (string) $dpi,
                '-png',
                $path,
                $prefix,
            ], 60, 'Konversi halaman PDF untuk OCR',
                'Pasang Poppler (pdftoppm / pdfinfo) lalu atur PDF_RENDERER_BINARY.');

            $pages = glob($prefix . '-*.png') ?: [];
            natsort($pages);
            if ($pages === []) {
                throw new RuntimeException('pdftoppm tidak menghasilkan gambar. Periksa apakah PDF berisi halaman yang valid.');
            }

            $text = [];
            $tesseract = config('services.pdf_ocr.tesseract_binary', 'tesseract');
            $languages = config('services.pdf_ocr.languages', 'ind+eng');

            foreach ($pages as $page) {
                try {
                    $text[] = $this->runProcess([
                        $tesseract,
                        $page,
                        'stdout',
                        '-l', $languages,
                        '--psm', '3',
                    ], 30, 'OCR halaman PDF',
                        'Pasang Tesseract OCR, bahasa Indonesia dan Inggris, lalu atur TESSERACT_BINARY.',
                        'Pastikan data bahasa Tesseract "ind" dan "eng" sudah terpasang.')->getOutput();
                } catch (RuntimeException $e) {
                    \Illuminate\Support\Facades\Log::warning('OCR 1 halaman gagal di-skip', [
                        'page'  => $page,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            return implode("\n", $text);
        } finally {
            $this->cleanupDirectory($directory);
        }
    }

    private function createTempDirectory(): string
    {
        $base = sys_get_temp_dir();
        for ($i = 0; $i < 5; $i++) {
            $directory = $base . DIRECTORY_SEPARATOR . 'mou-ocr-' . bin2hex(random_bytes(8));
            if (@mkdir($directory, 0700)) {
                return $directory;
            }
        }
        throw new RuntimeException('Folder sementara untuk OCR tidak dapat dibuat.');
    }

    private function cleanupDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            } elseif (is_dir($file)) {
                $this->cleanupDirectory($file);
            }
        }
        @rmdir($directory);
    }

    private function runProcess(
        array $command,
        int $timeout,
        string $stage,
        string $missingBinaryMessage,
        string $failureMessage = 'Pastikan PDF tidak rusak dan konfigurasi OCR sudah benar.',
    ): Process {
        $process = new Process($command);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException("{$stage} melewati batas waktu {$timeout} detik.", previous: $exception);
        } catch (ProcessStartFailedException $exception) {
            throw new RuntimeException("{$stage} tidak dapat dijalankan. {$missingBinaryMessage}", previous: $exception);
        }

        if (! $process->isSuccessful()) {
            $exitCode = (int) $process->getExitCode();
            $stderr   = trim($process->getErrorOutput());
            $stdout   = trim($process->getOutput());
            $detail   = $stderr !== '' ? $stderr : $stdout;
            $detail   = $detail !== ''
                ? mb_substr(preg_replace('/\s+/u', ' ', $detail), 0, 500)
                : '';

            if ($exitCode === 127
                || stripos($detail, 'command not found') !== false
                || stripos($detail, 'No such file') !== false
                || stripos($detail, 'not recognized') !== false) {
                $binary = $command[0] ?? '?';
                throw new RuntimeException("{$stage} gagal: binary '{$binary}' tidak ditemukan di PATH server. {$missingBinaryMessage}");
            }

            $suffix = $detail !== '' ? " Detail: {$detail}" : '';
            throw new RuntimeException("{$stage} gagal (exit {$exitCode}). {$failureMessage}{$suffix}");
        }

        return $process;
    }

    private function hasExtractedFields(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }
        return false;
    }

    public function extractFields(string $text): array
    {
        $lines   = preg_split('/\R/u', $text) ?: [];
        $numbers = [];

        foreach (array_slice($lines, 0, 40) as $line) {
            if (preg_match_all('/\b(?:nomor|no\.?)\s*[:.]?\s*([A-Z0-9][A-Z0-9.\/\_-]{2,})/iu', $line, $matches)) {
                foreach ($matches[1] as $number) {
                    $number = trim($number, " \t\n\r\0\x0B,;");
                    if ($number !== '' && ! in_array($number, $numbers, true)) {
                        $numbers[] = $number;
                    }
                }
            }
        }

        $dates = $this->extractDates($text);
        $title = $this->extractTitle($lines);

        return [
            'judul'                  => $title,
            'nomor_dokumen_polinema' => $numbers[0] ?? null,
            'nomor_dokumen_mitra'    => $numbers[1] ?? null,
            'tanggal_awal'           => $dates[0]['date'] ?? null,
            'tanggal_akhir'          => $this->extractEndDate($text, $dates),
        ];
    }

    private function extractTitle(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if (! preg_match('/\bTENTANG\b\s*(?:[:\-]\s*)?(.*)$/iu', $line, $match)) {
                continue;
            }

            $titleLines  = [];
            $inlineTitle = trim($match[1] ?? '');
            if ($inlineTitle !== '') {
                $titleLines[] = $inlineTitle;
            }

            for ($next = $index + 1; $next < min(count($lines), $index + 5); $next++) {
                $candidate = trim($lines[$next]);
                if ($candidate === '') {
                    continue;
                }
                if (preg_match('/^(?:BAB\b|PASAL\b|PIHAK\b|\d+\.\s)/iu', $candidate)) {
                    break;
                }
                if (count($titleLines) >= 3) {
                    break;
                }
                $titleLines[] = $candidate;
            }

            $title = trim(preg_replace('/\s+/u', ' ', implode(' ', $titleLines)) ?? '');
            return $title === '' ? null : mb_substr($title, 0, 255);
        }
        return null;
    }

    private function extractDates(string $text): array
    {
        $pattern = '/\b\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4}\b'
            . '|\b\d{1,2}[.\/-]\d{1,2}[.\/-]\d{4}\b'
            . '|\b\d{4}-\d{1,2}-\d{1,2}\b/iu';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        $dates = [];
        foreach ($matches[0] as [$value, $offset]) {
            $date = $this->normalizeDate($value);
            if ($date !== null) {
                $dates[] = ['date' => $date, 'offset' => $offset];
            }
        }
        return $dates;
    }

    private function normalizeDate(string $value): ?string
    {
        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];

        if (preg_match('/^(\d{1,2})\s+(\p{L}+)\s+(\d{4})$/iu', $value, $match)) {
            $day   = (int) $match[1];
            $month = $months[mb_strtolower($match[2])] ?? 0;
            $year  = (int) $match[3];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $match)) {
            $year  = (int) $match[1];
            $month = (int) $match[2];
            $day   = (int) $match[3];
        } elseif (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $value, $match)) {
            $day   = (int) $match[1];
            $month = (int) $match[2];
            $year  = (int) $match[3];
        } else {
            return null;
        }

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : null;
    }

    private function extractEndDate(string $text, array $dates): ?string
    {
        $startOffset = $dates[0]['offset'] ?? -1;

        foreach (array_slice($dates, 1) as $date) {
            if ($date['offset'] <= $startOffset) {
                continue;
            }
            $context = substr($text, max(0, $date['offset'] - 100), min(100, $date['offset']));
            if (preg_match('/\b(?:sampai(?:\s+dengan)?|hingga|berakhir|s\.d\.?)\b/iu', $context)) {
                return $date['date'];
            }
        }
        return null;
    }
}