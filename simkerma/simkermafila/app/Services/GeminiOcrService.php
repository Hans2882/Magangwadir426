<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiOcrService
{
    /**
     * Extracts information from a PDF file using Gemini 3.5 Flash.
     *
     * @param string $pdfContent The raw binary content of the PDF file.
     * @return array|null Returns an associative array of extracted data or null on failure.
     */
    public function extractFromPdfContent(string $pdfContent): ?array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            Log::error('Gemini API Key is missing.');
            return null;
        }

        if (empty($pdfContent)) {
            Log::error("PDF content is empty.");
            return null;
        }

        // Base64 encode the PDF
        $pdfData = base64_encode($pdfContent);

        $prompt = "You are a highly accurate data extraction assistant. Analyze the attached Indonesian cooperation document (MoU, MoA, IA, or PKS). Extract the following information and return it strictly as a single, valid JSON object, with no markdown formatting, no preamble, and no extra braces.\n"
                . "Use exactly these keys:\n"
                . "- nomor_dokumen_polinema (String, the first document number at the top, usually containing PL2)\n"
                . "- nomor_dokumen_mitra (String, the second document number at the top, belonging to the partner)\n"
                . "- tanggal_awal (Date in YYYY-MM-DD format, the start date or signing date)\n"
                . "- tanggal_akhir (Date in YYYY-MM-DD format, the end date if mentioned, otherwise null)\n"
                . "- judul (String, the specific title or subject of the agreement. ONLY include the text that comes exactly AFTER the word 'TENTANG')\n"
                . "- nama_mitra (String, the name of the external partner organization)";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => 'application/pdf',
                                'data' => $pdfData
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'response_mime_type' => 'application/json',
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout(60) // PDF processing can take a few seconds
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Extract just the JSON object from the response string
                $start = strpos($text, '{');
                $end = strrpos($text, '}');
                if ($start !== false && $end !== false && $end >= $start) {
                    $text = substr($text, $start, $end - $start + 1);
                }
                
                // Parse the JSON block
                $extracted = json_decode(trim($text), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $extracted;
                } else {
                    Log::error("Failed to parse Gemini JSON output: " . json_last_error_msg(), ['output' => $text]);
                }
            } else {
                Log::error("Gemini API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Gemini OCR Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Returns a Filament Form Action for Auto-Fill via AI.
     */
    public static function getAutoFillAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('autofill')
            ->label('Auto-Fill via AI')
            ->icon('heroicon-m-sparkles')
            ->action(function ($get, $set) {
                $state = $get('link_dokumen');
                if (!$state) {
                    \Filament\Notifications\Notification::make()->title('Upload file terlebih dahulu')->danger()->send();
                    return;
                }
                
                $file = is_array($state) ? array_values($state)[0] : $state;
                
                try {
                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $content = file_get_contents($file->getRealPath());
                    } else if (is_string($file)) {
                        $content = \Illuminate\Support\Facades\Storage::disk('google')->get($file);
                    } else {
                        \Filament\Notifications\Notification::make()->title('Format file tidak didukung')->danger()->send();
                        return;
                    }
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()->title('Gagal membaca file')->danger()->send();
                    return;
                }
                
                \Filament\Notifications\Notification::make()->title('Memproses dengan AI...')->info()->send();
                
                $service = new self();
                $data = $service->extractFromPdfContent($content);
                
                if ($data) {
                    if (!empty($data['nomor_dokumen_polinema'])) $set('nomor_dokumen_polinema', $data['nomor_dokumen_polinema']);
                    if (!empty($data['nomor_dokumen_mitra'])) $set('nomor_dokumen_mitra', $data['nomor_dokumen_mitra']);
                    if (!empty($data['tanggal_awal'])) $set('tanggal_awal', $data['tanggal_awal']);
                    if (!empty($data['tanggal_akhir'])) $set('tanggal_akhir', $data['tanggal_akhir']);
                    if (!empty($data['judul'])) $set('judul', $data['judul']);
                    if (!empty($data['nama_mitra'])) {
                        $mitra = \App\Models\Mitra::where('nama_mitra', 'like', '%' . $data['nama_mitra'] . '%', 'and')->first();
                        if ($mitra) $set('mitra_id', $mitra->id);
                    }
                    \Filament\Notifications\Notification::make()->title('Auto-Fill Berhasil!')->success()->send();
                } else {
                    \Filament\Notifications\Notification::make()->title('Gagal mengekstrak data')->danger()->send();
                }
            });
    }
}
