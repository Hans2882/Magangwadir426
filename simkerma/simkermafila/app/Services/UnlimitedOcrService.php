<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;

class UnlimitedOcrService
{
    /**
     * @param  resource  $stream
     */
    public function extractTextFromStream($stream): string
    {
        if (! is_resource($stream)) {
            throw new RuntimeException('File PDF tidak dapat dibuka dari penyimpanan.');
        }

        $path = tempnam(sys_get_temp_dir(), 'unlimited-ocr-pdf-');
        if ($path === false) {
            fclose($stream);
            throw new RuntimeException('File sementara untuk membaca PDF tidak dapat dibuat.');
        }

        try {
            $target = fopen($path, 'wb');
            if ($target === false) {
                throw new RuntimeException('File sementara untuk membaca PDF tidak dapat dibuka.');
            }

            try {
                $bytesCopied = stream_copy_to_stream($stream, $target, (25 * 1024 * 1024) + 1);
            } finally {
                fclose($target);
            }

            if ($bytesCopied === false) {
                throw new RuntimeException('File PDF gagal dibaca dari penyimpanan.');
            }
            if ($bytesCopied > 25 * 1024 * 1024) {
                throw new RuntimeException('Ukuran PDF melebihi batas 25 MB.');
            }

            return $this->extractTextFromPdfPath($path);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function extractTextFromPdfPath(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File PDF tidak dapat dibaca.');
        }

        $size = filesize($path);
        if ($size === false || $size > 25 * 1024 * 1024) {
            throw new RuntimeException('Ukuran PDF melebihi batas 25 MB.');
        }

        if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('File yang dipilih bukan PDF yang valid.');
        }

        $pages = $this->getPageCount($path);
        $maxPages = (int) config('services.unlimited_ocr.max_pages', 10);
        if ($maxPages > 0 && $pages > $maxPages) {
            throw new RuntimeException("PDF memiliki {$pages} halaman; batas Unlimited-OCR saat ini {$maxPages} halaman.");
        }

        $directory = tempnam(sys_get_temp_dir(), 'unlimited-ocr-');
        if ($directory === false) {
            throw new RuntimeException('Direktori sementara untuk OCR tidak dapat dibuat.');
        }
        if (! unlink($directory) || ! mkdir($directory, 0700)) {
            throw new RuntimeException('Direktori sementara untuk OCR tidak dapat dibuat.');
        }

        try {
            $prefix = $directory.DIRECTORY_SEPARATOR.'page';
            $this->runProcess([
                (string) config('services.pdf_ocr.renderer_binary', 'pdftoppm'),
                '-f', '1',
                '-l', (string) $pages,
                '-r', (string) config('services.unlimited_ocr.dpi', 150),
                '-jpeg',
                '-jpegopt', 'quality=85',
                $path,
                $prefix,
            ], 'PDF gagal diubah menjadi gambar. Pastikan Poppler pdftoppm terpasang.');

            $images = [];
            for ($page = 1; $page <= $pages; $page++) {
                $imagePath = $prefix.'-'.$page.'.jpg';
                if (! is_file($imagePath) || ! is_readable($imagePath)) {
                    throw new RuntimeException("Gambar halaman {$page} PDF tidak berhasil dibuat.");
                }
                $images[] = $imagePath;
            }

            return $this->recognizeImages($images);
        } finally {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    unlink($temporaryFile);
                }
            }
            rmdir($directory);
        }
    }

    /**
     * @param  list<string>  $imagePaths
     */
    public function recognizeImages(array $imagePaths): string
    {
        if ($imagePaths === []) {
            throw new RuntimeException('Tidak ada gambar halaman PDF untuk diproses.');
        }

        $content = [];
        foreach ($imagePaths as $imagePath) {
            if (! is_file($imagePath) || ! is_readable($imagePath)) {
                throw new RuntimeException('Gambar halaman PDF tidak dapat dibaca.');
            }

            $image = file_get_contents($imagePath);
            if ($image === false) {
                throw new RuntimeException('Gambar halaman PDF gagal dibaca.');
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/jpeg;base64,'.base64_encode($image),
                ],
            ];
        }

        $content[] = [
            'type' => 'text',
            'text' => str_repeat('<PAGE><image>', count($imagePaths)).'Multi page parsing.',
        ];

        $url = trim((string) config('services.unlimited_ocr.url'));
        if ($url === '') {
            throw new RuntimeException('URL server Unlimited-OCR belum dikonfigurasi.');
        }

        $pageCount = count($imagePaths);
        $payload = [
            'model' => (string) config('services.unlimited_ocr.model', 'Unlimited-OCR-2'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
            'max_tokens' => (int) config('services.unlimited_ocr.tokens_per_page', 8000) * $pageCount,
            'temperature' => 0,
            'skip_special_tokens' => false,
            'images_config' => ['crop_mode' => true],
            'stream' => false,
        ];

        $request = Http::acceptJson()
            ->connectTimeout((int) config('services.unlimited_ocr.connect_timeout', 10))
            ->timeout((int) config('services.unlimited_ocr.timeout', 600));

        $token = trim((string) config('services.unlimited_ocr.token', ''));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, $payload);
        if (! $response->successful()) {
            throw new RuntimeException("Server Unlimited-OCR mengembalikan HTTP {$response->status()}.");
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Server Unlimited-OCR tidak mengembalikan teks hasil OCR.');
        }

        return trim($text);
    }

    private function getPageCount(string $path): int
    {
        $process = new Process([
            (string) config('services.pdf_ocr.pdfinfo_binary', 'pdfinfo'),
            $path,
        ]);
        $process->setTimeout(15);
        $process->run();

        if (! $process->isSuccessful() || ! preg_match('/^Pages:\s+(\d+)\s*$/mi', $process->getOutput(), $matches)) {
            throw new RuntimeException('Jumlah halaman PDF tidak dapat diperiksa. Pastikan Poppler pdfinfo terpasang.');
        }

        $pages = (int) $matches[1];
        if ($pages < 1) {
            throw new RuntimeException('PDF tidak memiliki halaman yang dapat diproses.');
        }

        return $pages;
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command, string $failureMessage): void
    {
        $process = new Process($command);
        $process->setTimeout((int) config('services.unlimited_ocr.render_timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($failureMessage.' '.trim($process->getErrorOutput()));
        }
    }
}
