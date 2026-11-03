<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class MouPdfAutofillService
{
    private const MAX_PDF_SIZE = 20 * 1024 * 1024;

    /**
     * Batas teks yang dikirim ke Ollama.
     * Informasi MoU (judul, nomor, tanggal) hampir selalu ada
     * di header. Regex menangani tanggal di luar batas ini.
     */
    private const MAX_OLLAMA_TEXT_LENGTH = 12000;

    private const MAX_OCR_PAGES = 3;

    /**
     * pdftotext hanya membaca halaman awal.
     * Header MoU selalu di 1-3 halaman pertama.
     */
    private const PDFTOTEXT_MAX_PAGES = 5;

    private const CACHE_VERSION = 'v3';

    /**
     * Field minimum yang harus berhasil diekstrak.
     */
    private const CRITICAL_FIELDS = [
        'judul',
        'nomor_dokumen_polinema',
        'tanggal_awal',
    ];

    /**
     * Cache ringan per-instance untuk pemrosesan teks berulang
     * (extractFields dipanggil multi-stage dengan teks yang sama).
     */
    private array $textCache = [];

    /**
     * ================================================================
     * PUBLIC METHODS
     * ================================================================
     */

    /**
     * @param resource $stream
     */
    public function extractFromStream($stream, bool $useOllama = false): array
    {
        if (!is_resource($stream)) {
            throw new RuntimeException(
                'File PDF tidak dapat dibuka dari penyimpanan.'
            );
        }

        $path = tempnam(sys_get_temp_dir(), 'mou-pdf-');

        if ($path === false) {
            fclose($stream);

            throw new RuntimeException(
                'File sementara untuk membaca PDF tidak dapat dibuat.'
            );
        }

        try {
            $target = fopen($path, 'wb');

            if ($target === false) {
                throw new RuntimeException(
                    'File sementara untuk membaca PDF tidak dapat dibuka.'
                );
            }

            try {
                $bytesCopied = stream_copy_to_stream(
                    $stream,
                    $target,
                    self::MAX_PDF_SIZE + 1
                );
            } finally {
                fclose($target);
            }

            if ($bytesCopied === false) {
                throw new RuntimeException(
                    'File PDF gagal diunduh dari penyimpanan.'
                );
            }

            return $this->extractFromPdfPath($path, $useOllama);

        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Wrapper dengan cache by content-hash.
     */
    public function extractFromPdfPath(string $path, bool $useOllama = false): array
    {
        $useOllama = $useOllama && (bool) config('ollama-laravel.enabled', true);
        $cacheKey = $this->cacheKey($path, $useOllama);

        if ($cacheKey !== null && Cache::has($cacheKey)) {
            Log::info('MoU Autofill: cache hit.', ['key' => $cacheKey]);

            return Cache::get($cacheKey);
        }

        $result = $this->doExtractFromPdfPath($path, $useOllama);

        if ($cacheKey !== null) {
            $ttl = (int) config('services.mou.cache_ttl_hours', 24);

            if ($ttl > 0) {
                Cache::put(
                    $cacheKey,
                    $result,
                    now()->addHours($ttl)
                );
            }
        }

        return $result;
    }

    /**
     * Warmup model Ollama supaya tidak cold-start saat request user.
     * Panggil sekali di AppServiceProvider::boot() atau via scheduled job.
     */
    public function warmUpOllama(): bool
    {
        if (!config('ollama-laravel.enabled', true)) {
            return false;
        }

        $url = rtrim((string) config('ollama-laravel.url', ''), '/');
        $model = (string) config('ollama-laravel.model', '');

        if ($url === '' || $model === '') {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout(30)
                ->post($url . '/api/generate', [
                    'model' => $model,
                    'prompt' => '',
                    'keep_alive' => config(
                        'ollama-laravel.keep_alive',
                        '30m'
                    ),
                ]);

            Log::info('MoU: warmup Ollama.', [
                'success' => $response->successful(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('MoU: warmup Ollama gagal.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ================================================================
     * MAIN EXTRACTION PIPELINE
     * ================================================================
     */

    private function doExtractFromPdfPath(string $path, bool $useOllama): array
    {
        $totalStart = hrtime(true);

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('File PDF tidak dapat dibaca.');
        }

        $size = filesize($path);

        if ($size === false) {
            throw new RuntimeException('Ukuran file PDF tidak dapat diperiksa.');
        }

        if ($size > self::MAX_PDF_SIZE) {
            throw new RuntimeException('Ukuran PDF melebihi batas 20 MB.');
        }

        if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('File yang dipilih bukan PDF yang valid.');
        }

        $data = $this->emptyFields();
        $sourceText = '';

        /*
         * ============================================================
         * STAGE 1: PDFTOTEXT (halaman awal saja)
         * ============================================================
         */

        $stageStart = hrtime(true);

        try {
            $process = $this->runProcess([
                config('services.pdf_text_extractor.binary', 'pdftotext'),
                '-layout',
                '-nopgbrk',
                '-f', '1',
                '-l', (string) self::PDFTOTEXT_MAX_PAGES,
                $path,
                '-',
            ], $this->pdftotextTimeout($size),
                'Pembacaan teks PDF',
                'Pastikan pdftotext terpasang dan PDFTOTEXT_BINARY benar.'
            );

            $sourceText = trim($process->getOutput());
            $data = $this->extractFields($sourceText);

            $this->logStage('pdftotext', $stageStart, [
                'text_length' => mb_strlen($sourceText),
                'complete' => $this->isExtractionComplete($data),
            ]);

        } catch (RuntimeException $e) {
            $this->logStage('pdftotext', $stageStart, [
                'success' => false,
                'error' => $e->getMessage(),
            ]);

            Log::warning('MoU: pdftotext gagal, mencoba metode berikutnya.');
        }

        /*
         * Lazy pdfinfo: hanya jika pdftotext kosong / gagal.
         * Jika pdftotext menghasilkan teks, PDF jelas processable.
         */
        if ($sourceText === '') {
            $this->assertPdfIsProcessable($path);
        }

        if ($this->isDone($data, $sourceText)) {
            $this->logTotal($totalStart, 'pdftotext');

            return $data;
        }

        /*
         * ============================================================
         * STAGE 2: TESSERACT OCR (incremental + early exit)
         * ============================================================
         */

        if ($this->needsOcr($sourceText, $data)) {
            $stageStart = hrtime(true);

            try {
                $this->runIncrementalOcr(
                    $path,
                    $data,
                    $sourceText,
                    fn (): bool => $this->isDone($data, $sourceText)
                );

                $this->logStage('tesseract_ocr', $stageStart, [
                    'text_length' => mb_strlen($sourceText),
                    'complete' => $this->isExtractionComplete($data),
                ]);

            } catch (RuntimeException $e) {
                $this->logStage('tesseract_ocr', $stageStart, [
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);

                Log::warning('MoU: OCR gagal, melanjutkan dengan hasil lokal.');
            }

            if ($this->isDone($data, $sourceText)) {
                $this->logTotal($totalStart, 'tesseract_ocr');

                return $data;
            }
        } else {
            Log::info('MoU: OCR dilewati karena hasil pdftotext cukup.');
        }

        /*
         * ============================================================
         * STAGE 3: OLLAMA AI
         * ============================================================
         */

        $explicitNumbers = $this->extractExplicitDocumentNumbers($sourceText);

        $needsAi = (
            !$this->isExtractionComplete($data)
            || $this->needsNumberReview($data, $explicitNumbers)
        );

        if ($useOllama && $needsAi && $sourceText !== '') {
            $stageStart = hrtime(true);

            try {
                $aiData = $this->extractWithOllama($sourceText);

                if (is_array($aiData)) {
                    $this->applyAiFields($data, $aiData, $explicitNumbers);
                }

                $this->logStage('ollama', $stageStart, [
                    'success' => is_array($aiData),
                    'complete' => $this->isExtractionComplete($data),
                ]);

            } catch (\Throwable $e) {
                $this->logStage('ollama', $stageStart, [
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);

                Log::warning('MoU: Ollama gagal, memakai hasil ekstraksi lokal.');
            }
        } else {
            Log::info('MoU: Ollama dilewati.', [
                'requested' => $useOllama,
                'needed' => $needsAi,
            ]);
        }

        $this->logTotal(
            $totalStart,
            $this->isExtractionComplete($data) ? 'completed' : 'partial'
        );

        return $data;
    }

    /**
     * ================================================================
     * CACHING
     * ================================================================
     */

    private function cacheKey(string $path, bool $useOllama): ?string
    {
        if (!config('services.mou.cache_enabled', true)) {
            return null;
        }

        $hash = @hash_file('sha256', $path);

        if ($hash === false) {
            return null;
        }

        return sprintf(
            'mou:autofill:%s:%s:%s',
            self::CACHE_VERSION,
            $hash,
            $useOllama ? 'ai' : 'local'
        );
    }

    /**
     * ================================================================
     * FIELD HELPERS
     * ================================================================
     */

    private function emptyFields(): array
    {
        return [
            'judul' => null,
            'nomor_dokumen_polinema' => null,
            'nomor_dokumen_mitra' => null,
            'tanggal_awal' => null,
            'tanggal_akhir' => null,
        ];
    }

    private function isExtractionComplete(array $data): bool
    {
        foreach (self::CRITICAL_FIELDS as $field) {
            if (
                !isset($data[$field])
                || trim((string) $data[$field]) === ''
            ) {
                return false;
            }
        }

        return true;
    }

    private function needsOcr(string $text, array $data): bool
    {
        if (trim($text) === '') {
            return true;
        }

        return !$this->isExtractionComplete($data);
    }

    private function needsNumberReview(
        array $data,
        array $explicitNumbers
    ): bool {
        foreach ([
            'nomor_dokumen_polinema',
            'nomor_dokumen_mitra',
        ] as $field) {
            if (
                !empty($data[$field])
                && empty($explicitNumbers[$field])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kondisi berhenti: field kritis lengkap AND nomor tidak butuh review.
     */
    private function isDone(array $data, string $sourceText): bool
    {
        return $this->isExtractionComplete($data)
            && !$this->needsNumberReview(
                $data,
                $this->extractExplicitDocumentNumbers($sourceText)
            );
    }

    private function mergeFields(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (
                ($target[$key] ?? null) === null
                || $target[$key] === ''
            ) {
                $target[$key] = $value;
            }
        }
    }

    private function applyAiFields(
        array &$data,
        array $aiData,
        array $explicitNumbers
    ): void {
        foreach ($aiData as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $isDocNumber = in_array(
                $key,
                ['nomor_dokumen_polinema', 'nomor_dokumen_mitra'],
                true
            );

            /*
             * Nomor eksplisit dari dokumen tidak boleh ditimpa AI.
             * Nomor hasil fallback boleh dikoreksi AI.
             */
            if ($isDocNumber) {
                if (($explicitNumbers[$key] ?? null) === null) {
                    $data[$key] = $value;
                }

                continue;
            }

            if (
                ($data[$key] ?? null) === null
                || $data[$key] === ''
            ) {
                $data[$key] = $value;
            }
        }
    }

    /**
     * ================================================================
     * OLLAMA AI (tuned)
     * ================================================================
     */

    public function extractWithOllama(string $text): ?array
    {
        if (!config('ollama-laravel.enabled', true)) {
            Log::info('MoU: Ollama dinonaktifkan; ekstraksi tetap dilakukan secara lokal.');

            return null;
        }

        $url = rtrim((string) config('ollama-laravel.url', ''), '/');
        $model = (string) config('ollama-laravel.model', '');

        if ($text === '' || $url === '' || $model === '') {
            Log::warning('MoU: Konfigurasi Ollama belum tersedia.');

            return null;
        }

        $documentText = mb_substr(
            $text,
            0,
            self::MAX_OLLAMA_TEXT_LENGTH
        );

        $prompt = $this->ollamaPrompt();

        $timeout = min(
            15,
            max(1, (int) config('ollama-laravel.connection.timeout', 15))
        );

        $payload = [
            'model' => $model,
            'prompt' => $prompt . "\n\n" . $documentText,
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => 0,
                'num_predict' => 150,
                'num_ctx' => 4096,
            ],
        ];

        $keepAlive = config('ollama-laravel.keep_alive', '30m');

        if ($keepAlive !== null && $keepAlive !== '') {
            $payload['keep_alive'] = $keepAlive;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout($timeout)
                ->post($url . '/api/generate', $payload);

        } catch (ConnectionException $exception) {
            Log::warning('MoU: Ollama tidak dapat dihubungi.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            Log::warning('MoU: Ollama mengembalikan HTTP error.', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $responseText = $response->json('response');

        if (!is_string($responseText) || trim($responseText) === '') {
            Log::warning('MoU: Respons Ollama kosong atau tidak valid.');

            return null;
        }

        try {
            $fields = json_decode(
                $responseText,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $exception) {
            Log::warning('MoU: JSON Ollama tidak valid.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (!is_array($fields)) {
            return null;
        }

        return $this->validateOllamaFields($fields, $text);
    }

    private function ollamaPrompt(): string
    {
        return <<<'PROMPT'
You are an Indonesian legal document extraction assistant.

Extract information from the supplied Indonesian MoU document.

Return ONLY one valid JSON object with exactly these keys:

{
  "judul": null,
  "nomor_dokumen_polinema": null,
  "nomor_dokumen_mitra": null,
  "tanggal_awal": null,
  "tanggal_akhir": null
}

STRICT RULES:

1. Never invent information.
2. Use null when information is absent or uncertain.
3. Only extract information explicitly supported by the document.
4. Extract the agreement title after the word TENTANG.
5. Identify the Polinema document number using its label and context.
6. Identify the partner document number using its label and context.
7. Never assign document numbers based only on their order.
8. Dates must use YYYY-MM-DD format.
9. tanggal_akhir must only contain an explicitly stated end date.
10. Do not confuse signature dates with agreement start or end dates.
11. Do not treat document creation dates as agreement start dates unless the document explicitly indicates that meaning.
12. If affiliation is uncertain, return null.

DOCUMENT:
PROMPT;
    }

    /**
     * Validasi hasil AI terhadap teks asli.
     * Normalisasi teks dilakukan SEKALI (bukan per field).
     */
    private function validateOllamaFields(array $fields, string $text): array
    {
        $normalizedText = $this->normalizeText($text);

        $validated = [];

        foreach ([
            'judul',
            'nomor_dokumen_polinema',
            'nomor_dokumen_mitra',
        ] as $field) {
            $value = $fields[$field] ?? null;

            if (!is_string($value)) {
                continue;
            }

            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

            if ($value === '') {
                continue;
            }

            $maxLength = $field === 'judul' ? 255 : 100;

            if (
                mb_strlen($value) > $maxLength
                || !str_contains($normalizedText, $this->normalizeText($value))
                || (
                    $field !== 'judul'
                    && !preg_match('/\d/', $value)
                )
            ) {
                continue;
            }

            $validated[$field] = $value;
        }

        $dates = $this->extractDates($text);
        $explicitEndDate = $this->extractEndDate($text, $dates);
        $dateValues = array_column($dates, 'date');

        foreach (['tanggal_awal', 'tanggal_akhir'] as $field) {
            $value = $fields[$field] ?? null;

            if (!is_string($value)) {
                continue;
            }

            $date = $this->normalizeDate($value);

            if ($date === null) {
                continue;
            }

            $validEndDate = (
                $field !== 'tanggal_akhir'
                || $explicitEndDate === $date
            );

            if (
                in_array($date, $dateValues, true)
                && $validEndDate
            ) {
                $validated[$field] = $date;
            }
        }

        return $validated;
    }

    private function normalizeText(string $input): string
    {
        return mb_strtolower(
            trim(preg_replace('/\s+/u', ' ', $input) ?? '')
        );
    }

    /**
     * ================================================================
     * PDF VALIDATION (lazy - hanya dipanggil jika perlu)
     * ================================================================
     */

    private function assertPdfIsProcessable(string $path): void
    {
        $binary = (string) config(
            'services.pdf_ocr.pdfinfo_binary',
            'pdfinfo'
        );

        if ($binary === '') {
            return;
        }

        $process = new Process([$binary, $path]);
        $process->setTimeout(5);

        try {
            $process->run();

        } catch (\Throwable $e) {
            Log::warning('MoU: pdfinfo tidak tersedia, validasi dilewati.', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        if (!$process->isSuccessful()) {
            $blob = $stdout . "\n" . $stderr;

            if (preg_match('/password|encrypted|encryption/i', $blob)) {
                throw new RuntimeException(
                    'PDF dilindungi kata sandi atau terenkripsi. Hapus proteksi terlebih dahulu.'
                );
            }

            return;
        }

        if (preg_match('/^Encrypted:\s+yes/im', $stdout)) {
            throw new RuntimeException(
                'PDF terenkripsi. Hapus proteksi terlebih dahulu.'
            );
        }

        if (preg_match('/^Pages:\s*0\s*$/im', $stdout)) {
            throw new RuntimeException(
                'PDF tidak memiliki halaman yang dapat diproses.'
            );
        }
    }

    /**
     * ================================================================
     * TESSERACT OCR - INCREMENTAL + EARLY EXIT
     * ================================================================
     */

    private function runIncrementalOcr(
        string $path,
        array &$data,
        string &$sourceText,
        callable $isDone
    ): void {
        $dpi = max(
            120,
            min(150, (int) config('services.pdf_ocr.dpi', 130))
        );

        $this->runOcrPass($path, $dpi, $data, $sourceText, $isDone);

        /*
         * Retry di DPI lebih tinggi HANYA jika tidak ada teks sama sekali.
         * Kalau ada teks tapi field kurang, itu masalah format, bukan DPI.
         */
        if (trim($sourceText) === '' && $dpi < 200) {
            Log::info('MoU: OCR kosong, retry di DPI 200.');

            $this->runOcrPass($path, 200, $data, $sourceText, $isDone);
        }
    }

    private function runOcrPass(
        string $path,
        int $dpi,
        array &$data,
        string &$sourceText,
        callable $isDone
    ): void {
        $dir = $this->createTempDirectory();

        $renderer = config(
            'services.pdf_ocr.renderer_binary',
            'pdftoppm'
        );

        $tesseract = config(
            'services.pdf_ocr.tesseract_binary',
            'tesseract'
        );

        $languages = config(
            'services.pdf_ocr.languages',
            'ind+eng'
        );

        try {
            for ($page = 1; $page <= self::MAX_OCR_PAGES; $page++) {
                $prefix = $dir . DIRECTORY_SEPARATOR . 'page';
                $image = $prefix . '.png';

                try {
                    $this->runProcess([
                        $renderer,
                        '-f', (string) $page,
                        '-l', (string) $page,
                        '-r', (string) $dpi,
                        '-gray',
                        '-png',
                        '-singlefile',
                        $path,
                        $prefix,
                    ], 6, "Render halaman {$page} PDF untuk OCR",
                        'Pasang Poppler pdftoppm dan periksa PDF_RENDERER_BINARY.'
                    );
                } catch (RuntimeException $e) {
                    // Halaman habis atau render gagal - stop.
                    break;
                }

                if (!is_file($image)) {
                    break;
                }

                try {
                    $result = $this->runProcess([
                        $tesseract,
                        $image,
                        'stdout',
                        '-l', $languages,
                        '--psm', '3',
                        '--oem', '1',
                    ], 8, "OCR halaman {$page} PDF",
                        'Pasang Tesseract dan bahasa Indonesia serta Inggris.',
                        'Pastikan data bahasa Tesseract tersedia.'
                    );

                    $pageText = trim($result->getOutput());

                    if ($pageText !== '') {
                        $sourceText = trim($sourceText . "\n" . $pageText);

                        /*
                         * Parse teks akumulatif (bukan per-halaman) supaya
                         * konteks tanggal akhir lintas-halaman tetap terjaga.
                         */
                        $this->mergeFields(
                            $data,
                            $this->extractFields($sourceText)
                        );
                    }

                } catch (RuntimeException $e) {
                    Log::warning('MoU: OCR halaman gagal.', [
                        'page' => $page,
                        'error' => $e->getMessage(),
                    ]);
                } finally {
                    @unlink($image);
                }

                if ($isDone()) {
                    Log::info("MoU: OCR early-exit di halaman {$page}");

                    break;
                }
            }

            if (trim($sourceText) === '') {
                throw new RuntimeException(
                    'Tesseract tidak berhasil membaca teks pada halaman PDF.'
                );
            }

        } finally {
            $this->cleanupDirectory($dir);
        }
    }

    /**
     * ================================================================
     * TEMPORARY DIRECTORY
     * ================================================================
     */

    private function createTempDirectory(): string
    {
        $base = sys_get_temp_dir();

        for ($i = 0; $i < 5; $i++) {
            $directory = $base
                . DIRECTORY_SEPARATOR
                . 'mou-ocr-'
                . bin2hex(random_bytes(8));

            if (@mkdir($directory, 0700)) {
                return $directory;
            }
        }

        throw new RuntimeException(
            'Folder sementara untuk OCR tidak dapat dibuat.'
        );
    }

    private function cleanupDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
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

    /**
     * ================================================================
     * PROCESS EXECUTOR
     * ================================================================
     */

    private function runProcess(
        array $command,
        int $timeout,
        string $stage,
        string $missingBinaryMessage,
        string $failureMessage = 'Pastikan PDF tidak rusak dan konfigurasi sudah benar.'
    ): Process {
        $process = new Process($command);
        $process->setTimeout($timeout);

        try {
            $process->run();

        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException(
                "{$stage} melewati batas waktu {$timeout} detik.",
                previous: $exception
            );

        } catch (ProcessStartFailedException $exception) {
            throw new RuntimeException(
                "{$stage} tidak dapat dijalankan. {$missingBinaryMessage}",
                previous: $exception
            );
        }

        if (!$process->isSuccessful()) {
            $exitCode = (int) $process->getExitCode();

            $stderr = trim($process->getErrorOutput());
            $stdout = trim($process->getOutput());

            $detail = $stderr !== '' ? $stderr : $stdout;
            $detail = $detail !== ''
                ? mb_substr(
                    preg_replace('/\s+/u', ' ', $detail) ?? '',
                    0,
                    500
                )
                : '';

            if (
                $exitCode === 127
                || stripos($detail, 'command not found') !== false
                || stripos($detail, 'No such file') !== false
                || stripos($detail, 'not recognized') !== false
            ) {
                $binary = $command[0] ?? '?';

                throw new RuntimeException(
                    "{$stage} gagal: binary '{$binary}' tidak ditemukan. {$missingBinaryMessage}"
                );
            }

            $suffix = $detail !== '' ? " Detail: {$detail}" : '';

            throw new RuntimeException(
                "{$stage} gagal (exit {$exitCode}). {$failureMessage}{$suffix}"
            );
        }

        return $process;
    }

    private function pdftotextTimeout(int $size): int
    {
        if ($size < 500_000) {
            return 4;
        }

        if ($size < 2_000_000) {
            return 6;
        }

        if ($size < 10_000_000) {
            return 10;
        }

        return 15;
    }

    /**
     * ================================================================
     * PERFORMANCE LOGGING
     * ================================================================
     */

    private function logStage(
        string $stage,
        int $start,
        array $context = []
    ): void {
        $duration = round((hrtime(true) - $start) / 1_000_000, 2);

        Log::info(
            'MoU Autofill Stage',
            array_merge(
                ['stage' => $stage, 'duration_ms' => $duration],
                $context
            )
        );
    }

    private function logTotal(int $start, string $result): void
    {
        $duration = round((hrtime(true) - $start) / 1_000_000, 2);

        Log::info('MoU Autofill Total', [
            'result' => $result,
            'duration_ms' => $duration,
        ]);
    }

    /**
     * ================================================================
     * FIELD EXTRACTION (dengan cache per-instance)
     * ================================================================
     */

    public function extractFields(string $text): array
    {
        $key = 'fields:' . md5($text);

        if (isset($this->textCache[$key])) {
            return $this->textCache[$key];
        }

        return $this->textCache[$key] = $this->doExtractFields($text);
    }

    private function doExtractFields(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        $head = implode("\n", array_slice($lines, 0, 80));

        $explicitNumbers = $this->extractExplicitDocumentNumbers($head);

        $polinema = $explicitNumbers['nomor_dokumen_polinema'];
        $mitra = $explicitNumbers['nomor_dokumen_mitra'];

        if ($polinema === null || $mitra === null) {
            $candidates = [];

            $pattern = '/\b(?:'
                . 'nomor|no\.?'
                . '|administrasi\s+pihak\s+(?:perguruan\s+tinggi|mitra|polinema)'
                . ')\s*(?:dokumen(?:\s+\w+)?\s*)?[:.]?\s*'
                . '([A-Z0-9][A-Z0-9._\/-]{2,})/iu';

            if (preg_match_all($pattern, $head, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $candidate = trim(
                        $candidate,
                        " \t\n\r\0\x0B,;."
                    );

                    if (
                        $candidate === ''
                        || !preg_match('/\d/', $candidate)
                        || in_array($candidate, $candidates, true)
                    ) {
                        continue;
                    }

                    $candidates[] = $candidate;
                }
            }

            $remaining = array_values(
                array_filter(
                    $candidates,
                    fn ($candidate) => $candidate !== $polinema
                        && $candidate !== $mitra
                )
            );

            if ($polinema === null) {
                $polinema = array_shift($remaining);
            }

            if ($mitra === null) {
                $mitra = array_shift($remaining);
            }
        }

        $dates = $this->extractDates($text);

        return [
            'judul' => $this->extractTitle($lines),
            'nomor_dokumen_polinema' => $polinema,
            'nomor_dokumen_mitra' => $mitra,
            'tanggal_awal' => $dates[0]['date'] ?? null,
            'tanggal_akhir' => $this->extractEndDate($text, $dates),
        ];
    }

    /**
     * ================================================================
     * EXPLICIT DOCUMENT NUMBERS
     * ================================================================
     */

    private function extractExplicitDocumentNumbers(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $head = implode("\n", array_slice($lines, 0, 80));

        return [
            'nomor_dokumen_polinema' => $this->extractDocumentNumber(
                $head,
                '(?:'
                    . 'nomor\s+(?:dokumen\s+)?polinema'
                    . '|administrasi\s+pihak\s+perguruan\s+tinggi'
                    . '|administrasi\s+polinema'
                    . ')'
            ),
            'nomor_dokumen_mitra' => $this->extractDocumentNumber(
                $head,
                '(?:'
                    . 'nomor\s+(?:dokumen\s+)?mitra'
                    . '|administrasi\s+pihak\s+mitra'
                    . ')'
            ),
        ];
    }

    private function extractDocumentNumber(
        string $text,
        string $labelPattern
    ): ?string {
        $pattern = '~' . $labelPattern
            . '\s*[:.]?\s*'
            . '([A-Z0-9][A-Z0-9._/-]{2,})~iu';

        if (!preg_match_all($pattern, $text, $matches)) {
            return null;
        }

        foreach ($matches[1] as $candidate) {
            $value = trim($candidate, " \t\n\r\0\x0B,;.");

            if (preg_match('/\d/', $value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * ================================================================
     * TITLE EXTRACTION
     * ================================================================
     */

    private function extractTitle(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if (!preg_match(
                '/\bTENTANG\b\s*(?:[:\-]\s*)?(.*)$/iu',
                $line,
                $match
            )) {
                continue;
            }

            $titleLines = [];

            $inlineTitle = trim($match[1] ?? '');

            if ($inlineTitle !== '') {
                $titleLines[] = $inlineTitle;
            }

            for (
                $next = $index + 1;
                $next < min(count($lines), $index + 5);
                $next++
            ) {
                $candidate = trim($lines[$next]);

                if ($candidate === '') {
                    continue;
                }

                if (preg_match(
                    '/^(?:BAB\b|PASAL\b|PIHAK\b|ANTARA\b|\d+\.\s)/iu',
                    $candidate
                )) {
                    break;
                }

                if (preg_match(
                    '/^(?:Nomor|No\.?|Pada hari|Yang bertanda tangan)/iu',
                    $candidate
                )) {
                    break;
                }

                if (count($titleLines) >= 3) {
                    break;
                }

                $titleLines[] = $candidate;
            }

            $title = trim(
                preg_replace('/\s+/u', ' ', implode(' ', $titleLines)) ?? ''
            );

            return $title === '' ? null : mb_substr($title, 0, 255);
        }

        return null;
    }

    /**
     * ================================================================
     * DATE EXTRACTION (dengan cache per-instance)
     * ================================================================
     */

    private function extractDates(string $text): array
    {
        $key = 'dates:' . md5($text);

        if (isset($this->textCache[$key])) {
            return $this->textCache[$key];
        }

        return $this->textCache[$key] = $this->doExtractDates($text);
    }

    private function doExtractDates(string $text): array
    {
        $pattern = '~\b\d{1,2}\s+'
            . '(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)'
            . '\s+\d{4}\b'
            . '|\b\d{1,2}[./-]\d{1,2}[./-]\d{4}\b'
            . '|\b\d{4}-\d{1,2}-\d{1,2}\b~iu';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        $dates = [];
        $seen = [];

        foreach ($matches[0] as [$value, $offset]) {
            $date = $this->normalizeDate($value);

            if ($date === null || in_array($date, $seen, true)) {
                continue;
            }

            $seen[] = $date;

            $dates[] = [
                'date' => $date,
                'offset' => $offset,
            ];
        }

        return $dates;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3,
            'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];

        if (preg_match(
            '/^(\d{1,2})\s+(\p{L}+)\s+(\d{4})$/iu',
            $value,
            $match
        )) {
            $day = (int) $match[1];
            $month = $months[mb_strtolower($match[2])] ?? 0;
            $year = (int) $match[3];

        } elseif (preg_match(
            '/^(\d{4})-(\d{1,2})-(\d{1,2})$/',
            $value,
            $match
        )) {
            $year = (int) $match[1];
            $month = (int) $match[2];
            $day = (int) $match[3];

        } elseif (preg_match(
            '/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/',
            $value,
            $match
        )) {
            $day = (int) $match[1];
            $month = (int) $match[2];
            $year = (int) $match[3];

        } else {
            return null;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * ================================================================
     * END DATE EXTRACTION
     * ================================================================
     */

    private function extractEndDate(string $text, array $dates): ?string
    {
        $startOffset = $dates[0]['offset'] ?? -1;

        foreach (array_slice($dates, 1) as $date) {
            if ($date['offset'] <= $startOffset) {
                continue;
            }

            $context = substr(
                $text,
                max(0, $date['offset'] - 100),
                min(100, $date['offset'])
            );

            if (preg_match(
                '/\b(?:sampai(?:\s+dengan)?|hingga|berakhir)\b|s\s*\.?\s*d\.?/iu',
                $context
            )) {
                return $date['date'];
            }
        }

        return null;
    }
}