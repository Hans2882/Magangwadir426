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

        $prompt = "You are a highly accurate data extraction assistant. Analyze the attached Indonesian cooperation document (MoU, MoA, IA, PKS, or Laporan Kegiatan/Case Study). Extract the following information and return it strictly as a single, valid JSON object, with no markdown formatting, no preamble, and no extra braces.\n"
                . "Use exactly these keys:\n"
                . "- nomor_dokumen_polinema (String, the first document number at the top, usually containing PL2, or the 'Surat Tugas' number for activity reports)\n"
                . "- nomor_dokumen_mitra (String, the second document number at the top, belonging to the partner. Leave blank for activity reports)\n"
                . "- tanggal_awal (Date in YYYY-MM-DD format, the start date, signing date, or the date of the activity)\n"
                . "- tanggal_akhir (Date in YYYY-MM-DD format, the end date if mentioned, otherwise null)\n"
                . "- judul (String, the specific title or subject of the agreement or activity. For agreements, include text AFTER 'TENTANG'. For reports, include the main activity title)\n"
                . "- nama_mitra (String, the name of the external partner organization or university)\n"
                . "- nama_provinsi (String, the name of the province mentioned in the document for the partner's location)\n"
                . "- nama_kota (String, the name of the city mentioned in the document for the partner's location)\n"
                . "- link_laporan_kegiatan (String, a URL or link mentioned in the document referring to an activity report, Google Drive, or evidence link, otherwise null)\n"
                . "- prodis (Array of Strings, list of 'Program Studi' or 'Prodi' mentioned in the document. IMPORTANT: Extract ONLY the major name, do not include the word 'Program Studi' or 'Prodi'. Standardize degree prefixes from Roman numerals to alphanumeric, e.g., 'D-III' -> 'D3', 'S-I' -> 'S1'. For 'D-IV' or 'D4', change it to 'Sarjana Terapan'. Example: 'Program Studi D-IV Administrasi Bisnis' should be extracted strictly as 'Sarjana Terapan Administrasi Bisnis')\n"
                . "- jurusans (Array of Strings, list of 'Jurusan' mentioned in the document)";

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
            ->requiresConfirmation()
            ->modalHeading('Ekstrak Data Otomatis')
            ->modalDescription('Sistem akan membaca dokumen dan mengisi form secara otomatis. Proses ini mungkin memakan waktu 5-15 detik.')
            ->modalSubmitActionLabel('Mulai Proses')
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
                
                \Filament\Notifications\Notification::make()->title('Proses')->info()->send();
                
                $service = new self();
                $data = $service->extractFromPdfContent($content);
                
                if ($data) {
                    if (!empty($data['nomor_dokumen_polinema'])) $set('nomor_dokumen_polinema', $data['nomor_dokumen_polinema']);
                    if (!empty($data['nomor_dokumen_mitra'])) $set('nomor_dokumen_mitra', $data['nomor_dokumen_mitra']);
                    if (!empty($data['tanggal_awal'])) $set('tanggal_awal', $data['tanggal_awal']);
                    if (!empty($data['tanggal_akhir'])) $set('tanggal_akhir', $data['tanggal_akhir']);
                    if (!empty($data['judul'])) $set('judul', $data['judul']);
                    if (!empty($data['link_laporan_kegiatan'])) $set('link_laporan_kegiatan', $data['link_laporan_kegiatan']);
                    
                    if (!empty($data['nama_mitra'])) {
                        $mitra = \App\Models\Mitra::query()->where('nama_mitra', 'like', '%' . $data['nama_mitra'] . '%')->first();
                        if ($mitra) {
                            $set('mitra_id', $mitra->id);
                            
                            // Auto-select the most recent MoU, PKS, or IA for this Mitra
                            $parentDoc = \App\Models\Kerjasama::query()->where('mitra_id', $mitra->id)
                                ->whereIn('jenis_dokumen_id', [1, 3, 4]) // MoU, PKS, IA
                                ->latest()
                                ->first();
                                
                            if ($parentDoc) {
                                $set('parent_id', $parentDoc->id);
                            }
                        }
                    }
                    if (!empty($data['nama_provinsi'])) {
                        $provinsi = \App\Models\MasterProvinsi::query()->where('nama_provinsi', 'like', '%' . $data['nama_provinsi'] . '%')->first();
                        if ($provinsi) {
                            $set('provinsi_id', $provinsi->id);
                            
                            // If province is found and city is provided, search city within that province
                            if (!empty($data['nama_kota'])) {
                                $kota = \App\Models\MasterKota::query()->where('provinsi_id', $provinsi->id)
                                    ->where('nama_kota', 'like', '%' . $data['nama_kota'] . '%')
                                    ->first();
                                if ($kota) {
                                    $set('kota_id', $kota->id);
                                }
                            }
                        }
                    } elseif (!empty($data['nama_kota'])) {
                        // If no province was found/extracted, just try to find the city directly
                        $kota = \App\Models\MasterKota::query()->where('nama_kota', 'like', '%' . $data['nama_kota'] . '%')->first();
                        if ($kota) {
                            $set('kota_id', $kota->id);
                            // Auto-set the province from the city if we found the city directly
                            if ($kota->provinsi_id) {
                                $set('provinsi_id', $kota->provinsi_id);
                            }
                        }
                    }
                    $jurusanIds = [];
                    if (!empty($data['jurusans']) && is_array($data['jurusans'])) {
                        foreach ($data['jurusans'] as $jurusanName) {
                            $j = \App\Models\MasterJurusan::query()->where('nama_jurusan', 'like', '%' . $jurusanName . '%')->first();
                            if ($j && !in_array($j->id, $jurusanIds)) $jurusanIds[] = $j->id;
                        }
                    }

                    if (!empty($data['prodis']) && is_array($data['prodis'])) {
                        $prodiIds = [];
                        foreach ($data['prodis'] as $prodiName) {
                            // Extra sanitization just in case AI didn't catch it
                            $searchName = trim(str_ireplace(['Program Studi', 'Prodi'], '', $prodiName));
                            $searchName = str_replace(['D-IV', 'D4', 'D-III', 'D-II', 'D-I', 'S-I', 'S-II', 'S-III'], ['Sarjana Terapan', 'Sarjana Terapan', 'D3', 'D2', 'D1', 'S1', 'S2', 'S3'], $searchName);

                            $p = \App\Models\MasterProgramStudi::query()->where('nama_prodi', 'like', '%' . $searchName . '%')->first();
                            if ($p) {
                                $prodiIds[] = $p->id;
                                if ($p->jurusan_id && !in_array($p->jurusan_id, $jurusanIds)) {
                                    $jurusanIds[] = $p->jurusan_id;
                                }
                            }
                        }
                        if (!empty($prodiIds)) $set('prodis', $prodiIds);
                    }
                    
                    if (!empty($jurusanIds)) {
                        $set('jurusans', $jurusanIds);
                    }
                    \Filament\Notifications\Notification::make()->title('Auto-Fill Berhasil!')->success()->send();
                } else {
                    \Filament\Notifications\Notification::make()->title('Gagal mengekstrak data')->danger()->send();
                }
            });
    }
}
