<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TesseractOcrService
{
    /**
     * Check if Tesseract is enabled and binary exists.
     */
    public static function isAvailable(): bool
    {
        if (!config('services.tesseract.enabled', true)) {
            return false;
        }

        $binaryPath = self::getTesseractBinary();
        return !empty($binaryPath) && file_exists($binaryPath);
    }

    /**
     * Resolve the Tesseract executable path.
     */
    public static function getTesseractBinary(): ?string
    {
        $configured = config('services.tesseract.path');
        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        // Standard Windows fallback paths
        $defaultWindows = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];
        foreach ($defaultWindows as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Linux / system PATH check
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $process = Process::fromShellCommandline('which tesseract');
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        return null;
    }

    /**
     * Resolve pdftotext executable path if available.
     */
    public static function getPdfToTextBinary(): ?string
    {
        $candidates = [
            'D:\\SWB\\Laragon\\bin\\git\\mingw64\\bin\\pdftotext.exe',
            'C:\\Program Files\\poppler\\bin\\pdftotext.exe',
            'C:\\poppler\\bin\\pdftotext.exe',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Check system PATH
        $cmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where pdftotext' : 'which pdftotext';
        $process = Process::fromShellCommandline($cmd);
        $process->run();
        if ($process->isSuccessful()) {
            $output = trim(explode("\n", $process->getOutput())[0] ?? '');
            if (!empty($output) && file_exists($output)) {
                return $output;
            }
        }

        return null;
    }

    /**
     * Main entry point to extract text and evaluate quality.
     *
     * @param string $fileContent Raw binary content of the file
     * @param string $filename Original filename for extension hint
     * @return array
     */
    public function process(string $fileContent, string $filename = ''): array
    {
        if (empty($fileContent)) {
            return [
                'success' => false,
                'text' => '',
                'is_struggling' => true,
                'reason' => 'Konten berkas kosong',
                'source' => null,
            ];
        }

        $isPdf = str_starts_with($fileContent, '%PDF') || str_ends_with(strtolower($filename), '.pdf');

        if ($isPdf) {
            return $this->processPdf($fileContent, $filename);
        } else {
            return $this->processImage($fileContent, $filename);
        }
    }

    /**
     * Process PDF file (attempt pdftotext first, then scanned image extraction + tesseract).
     */
    protected function processPdf(string $pdfContent, string $filename): array
    {
        // 1. Attempt digital text extraction via pdftotext (ultra-fast for vector/digital PDFs)
        $pdftotextBin = self::getPdfToTextBinary();
        if (!empty($pdftotextBin)) {
            $tempPdf = tempnam(sys_get_temp_dir(), 'simkerma_pdf_') . '.pdf';
            file_put_contents($tempPdf, $pdfContent);

            try {
                $process = new Process([$pdftotextBin, '-f', '1', '-l', '3', $tempPdf, '-']);
                $process->setTimeout(10);
                $process->run();

                if ($process->isSuccessful()) {
                    $rawText = trim($process->getOutput());
                    $quality = $this->evaluateQuality($rawText);

                    if (!$quality['is_struggling']) {
                        @unlink($tempPdf);
                        return [
                            'success' => true,
                            'text' => $rawText,
                            'is_struggling' => false,
                            'reason' => null,
                            'source' => 'pdftotext_digital',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('pdftotext extraction error: ' . $e->getMessage());
            } finally {
                if (file_exists($tempPdf)) {
                    @unlink($tempPdf);
                }
            }
        }

        // 2. If pdftotext yields little/no text (scanned PDF), extract embedded image streams
        if (!self::isAvailable()) {
            return [
                'success' => false,
                'text' => '',
                'is_struggling' => true,
                'reason' => 'Tesseract OCR tidak aktif atau binary tidak ditemukan',
                'source' => null,
            ];
        }

        $extractedImages = $this->extractImagesFromPdfStream($pdfContent, 2);
        if (!empty($extractedImages)) {
            $combinedText = '';
            foreach ($extractedImages as $idx => $imgBytes) {
                $tempImg = tempnam(sys_get_temp_dir(), "ocr_page_{$idx}_") . '.jpg';
                file_put_contents($tempImg, $imgBytes);

                $pageText = $this->runTesseractOnImage($tempImg);
                @unlink($tempImg);

                if (!empty($pageText)) {
                    $combinedText .= "\n" . $pageText;
                }
            }

            $combinedText = trim($combinedText);
            $quality = $this->evaluateQuality($combinedText);

            return [
                'success' => !empty($combinedText),
                'text' => $combinedText,
                'is_struggling' => $quality['is_struggling'],
                'reason' => $quality['reason'],
                'source' => 'tesseract_scanned_pdf',
            ];
        }

        // No digital text and no embedded images could be extracted
        return [
            'success' => false,
            'text' => '',
            'is_struggling' => true,
            'reason' => 'Tidak dapat menemukan teks digital maupun citra scan pada berkas PDF',
            'source' => null,
        ];
    }

    /**
     * Process direct image file (PNG, JPG, TIFF).
     */
    protected function processImage(string $imageContent, string $filename): array
    {
        if (!self::isAvailable()) {
            return [
                'success' => false,
                'text' => '',
                'is_struggling' => true,
                'reason' => 'Tesseract OCR tidak aktif atau binary tidak ditemukan',
                'source' => null,
            ];
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'png';
        $tempImg = tempnam(sys_get_temp_dir(), 'ocr_direct_') . '.' . $ext;
        file_put_contents($tempImg, $imageContent);

        $text = $this->runTesseractOnImage($tempImg);
        @unlink($tempImg);

        $quality = $this->evaluateQuality($text);

        return [
            'success' => !empty($text),
            'text' => $text,
            'is_struggling' => $quality['is_struggling'],
            'reason' => $quality['reason'],
            'source' => 'tesseract_direct_image',
        ];
    }

    /**
     * Run Tesseract CLI command on an image file.
     */
    protected function runTesseractOnImage(string $imagePath): string
    {
        $binary = self::getTesseractBinary();
        if (!$binary) {
            return '';
        }

        $lang = config('services.tesseract.lang', 'ind+eng');

        try {
            $process = new Process([$binary, $imagePath, 'stdout', '-l', $lang]);
            $process->setTimeout(20);
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            } else {
                Log::warning('Tesseract execution failed: ' . $process->getErrorOutput());
                return '';
            }
        } catch (\Exception $e) {
            Log::error('Tesseract Exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract JPEG streams from raw PDF content (scanners wrap page scans as DCTDecode JPEGs).
     */
    protected function extractImagesFromPdfStream(string $pdfContent, int $maxPages = 2): array
    {
        $images = [];
        $offset = 0;
        $count = 0;

        while (($start = strpos($pdfContent, "\xFF\xD8\xFF", $offset)) !== false && $count < $maxPages) {
            $end = strpos($pdfContent, "\xFF\xD9", $start);
            if ($end !== false) {
                $images[] = substr($pdfContent, $start, ($end - $start) + 2);
                $offset = $end + 2;
                $count++;
            } else {
                break;
            }
        }

        return $images;
    }

    /**
     * Quality Gate to determine if Tesseract struggled with the document.
     */
    public function evaluateQuality(string $text): array
    {
        $clean = trim($text);
        $len = strlen($clean);

        // 1. Text is too short
        if ($len < 80) {
            return [
                'is_struggling' => true,
                'reason' => 'Teks hasil OCR terlalu pendek (< 80 karakter)',
                'length' => $len,
                'noise_ratio' => 1.0,
            ];
        }

        // 2. Check noise / gibberish ratio
        // Count alphanumeric characters and standard punctuation vs total characters
        $validChars = preg_match_all('/[a-zA-Z0-9\s\.\,\-\/\:\(\)\'\"\–\—]/', $clean);
        $noiseRatio = 1.0 - ($validChars / max($len, 1));

        if ($noiseRatio > 0.30) {
            return [
                'is_struggling' => true,
                'reason' => 'Tingkat karakter noise / halusinasi terlalu tinggi (' . round($noiseRatio * 100) . '%)',
                'length' => $len,
                'noise_ratio' => $noiseRatio,
            ];
        }

        // 3. Count meaningful words (words with length >= 3)
        $words = preg_split('/\s+/', $clean);
        $meaningfulWords = array_filter($words, fn($w) => strlen($w) >= 3 && preg_match('/[a-zA-Z]/', $w));

        if (count($meaningfulWords) < 8) {
            return [
                'is_struggling' => true,
                'reason' => 'Jumlah kata bermakna kurang dari 8 kata',
                'length' => $len,
                'noise_ratio' => $noiseRatio,
            ];
        }

        return [
            'is_struggling' => false,
            'reason' => null,
            'length' => $len,
            'noise_ratio' => $noiseRatio,
        ];
    }
}

