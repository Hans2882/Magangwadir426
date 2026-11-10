<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiOcrService
{
    /**
     * Extracts information from a PDF file using Gemini 3.6 Flash (Multimodal).
     *
     * @param string $pdfContent The raw binary content of the PDF file.
     * @param string $filename The filename of the PDF (optional, used as a hint).
     * @return array Returns an associative array of extracted data or error.
     */
    public function extractFromPdfContent(string $pdfContent, string $filename = ''): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            Log::error('Gemini API Key is missing.');
            return ['success' => false, 'error' => 'API Key Gemini belum diatur di server (.env).'];
        }

        if (empty($pdfContent)) {
            Log::error("PDF content is empty.");
            return ['success' => false, 'error' => 'Konten PDF kosong atau gagal terbaca.'];
        }

        $prompt = $this->buildExtractionPrompt($filename);
        $pdfData = base64_encode($pdfContent);

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

        return $this->executeGeminiRequest($payload, $apiKey);
    }

    /**
     * Extracts structured information from raw OCR text using Gemini 3.6 Flash (Text Prompt).
     *
     * @param string $rawText The raw text extracted locally by Tesseract / pdftotext.
     * @param string $filename Optional filename for hint.
     * @param string $sourceHint Optional note about the local extractor.
     * @return array
     */
    public function extractFromRawText(string $rawText, string $filename = '', string $sourceHint = ''): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            Log::error('Gemini API Key is missing.');
            return ['success' => false, 'error' => 'API Key Gemini belum diatur di server (.env).'];
        }

        if (empty($rawText)) {
            return ['success' => false, 'error' => 'Teks mentah hasil OCR kosong.'];
        }

        $basePrompt = $this->buildExtractionPrompt($filename);
        $fullPrompt = $basePrompt . "\n\nBerikut adalah teks mentah hasil ekstraksi OCR dokumen secara lokal ({$sourceHint}):\n"
                    . "--- AWAL TEKS DOKUMEN ---\n"
                    . $rawText . "\n"
                    . "--- AKHIR TEKS DOKUMEN ---\n\n"
                    . "Petakan dan lengkapi seluruh field JSON di atas berdasarkan teks dokumen ini!";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $fullPrompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'response_mime_type' => 'application/json',
            ]
        ];

        return $this->executeGeminiRequest($payload, $apiKey);
    }

    /**
     * Builds the standard JSON schema and extraction prompt for Indonesian cooperation documents.
     */
    protected function buildExtractionPrompt(string $filename = ''): string
    {
        $kategoriList = \App\Models\MasterMitraIku::pluck('kategori', 'id')->toArray();
        $bidangList = \App\Models\MasterKegiatan::pluck('bidang_kerjasama', 'id')->toArray();
        $jenisDokumenList = \App\Models\JenisDokumen::pluck('nama', 'id')->toArray();
        
        $kategoriString = json_encode($kategoriList);
        $bidangString = json_encode($bidangList);
        $jenisDokumenString = json_encode($jenisDokumenList);

        $prompt = "You are a highly accurate data extraction assistant. Analyze the attached Indonesian cooperation document (MoU, MoA, IA, PKS, or Laporan Kegiatan/Case Study). Extract the following information and return it strictly as a single, valid JSON object, with no markdown formatting, no preamble, and no extra braces.\n"
                . "Use exactly these keys:\n"
                . "- nomor_dokumen_polinema (String, the first document number at the top, usually containing PL2, or the 'Surat Tugas' number for activity reports)\n"
                . "- nomor_dokumen_mitra (String, the second document number at the top, belonging to the partner. Leave blank for activity reports)\n"
                . "- tanggal_awal (Date in YYYY-MM-DD format, the start date, signing date, or the date of the activity)\n"
                . "- tanggal_akhir (Date in YYYY-MM-DD format, the end date if mentioned, otherwise null)\n"
                . "- judul (String, the specific title or subject of the agreement or activity. For agreements, include text AFTER 'TENTANG'. For reports, include the main activity title)\n"
                . "- nama_mitra (String, the name of the external partner organization or university)\n"
                . "- alamat_mitra (String, the full address of the partner organization. If not explicitly mentioned in the document, use your world knowledge to find and provide their official headquarters address)\n"
                . "- email_mitra (String, the email address of the partner organization, if mentioned)\n"
                . "- telepon_mitra (String, the phone number of the partner organization, if mentioned)\n"
                . "- nama_negara (String, guess the country of the partner based on context/address, e.g. 'Indonesia', 'Malaysia')\n"
                . "- nama_provinsi (String, the province of the partner. If not explicitly mentioned in the document, use your world knowledge to determine it based on the partner's city or headquarters location)\n"
                . "- nama_kota (String, the city of the partner. If not explicitly mentioned in the document, use your world knowledge to determine it based on the partner's official headquarters location)\n"
                . "- kategori_id (Integer or null, guess the category ID of the partner based on their name. Use ONLY one of the keys from this exact mapping: $kategoriString. Context for the categories:\n"
                . "  - 'perusahaan swasta' includes perusahaan nasional, multinasional, startup, UMKM, dst.\n"
                . "  - 'lembaga/organisasi nirlaba' includes world-class non-profits, universities, research institutions.\n"
                . "  - 'institusi/organisasi multilateral' includes PBB, UNICEF, dsb.\n"
                . "  - 'instansi Pemerintah, BUMN, atau BUMD' includes government agencies, regional government, state-owned enterprises.)\n"
                . "- bidang_id (Integer or null, guess the collaboration field (Bidang Kerjasama) based on the document title and content. Use ONLY one of the keys from this exact mapping: $bidangString)\n"
                . "- jenis_dokumen_id (Integer or null, guess the document type (e.g. MoU, LoI, PKS, IA) based on the document title and content. Use ONLY one of the keys from this exact mapping: $jenisDokumenString)\n"
                . "- jenis (String, guess the scope or Cakupan (DN/LN) based on the partner's country. Must be EXACTLY 'Dalam Negeri' if the partner is from Indonesia, or 'Luar Negeri' if the partner is from outside Indonesia)\n"
                . "- link_laporan_kegiatan (String, a URL or link mentioned in the document referring to an activity report, Google Drive, or evidence link, otherwise null)\n"
                . "- prodis (Array of Strings, list of 'Program Studi' or 'Prodi' mentioned in the document. IMPORTANT: Extract ONLY the major name, do not include the word 'Program Studi' or 'Prodi'. Standardize degree prefixes from Roman numerals to alphanumeric, e.g., 'D-III' -> 'D3', 'S-I' -> 'S1'. For 'D-IV' or 'D4', change it to 'Sarjana Terapan'. Example: 'Program Studi D-IV Administrasi Bisnis' should be extracted strictly as 'Sarjana Terapan Administrasi Bisnis')\n"
                . "- jurusans (Array of Strings, list of 'Jurusan' mentioned in the document)\n";

        if (!empty($filename)) {
            $prompt .= "\nIMPORTANT HINT: The filename is '{$filename}'. Pay close attention to acronyms in the filename (like SMA vs SMK). Use the filename to correct or double-check the partner's name if the document scan is blurry or ambiguous.";
        }

        return $prompt;
    }

    /**
     * Executes the Gemini API call and returns the parsed JSON array.
     */
    protected function executeGeminiRequest(array $payload, string $apiKey): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->retry(3, 1500, function ($exception, $request) {
                return true;
            }, throw: false)
            ->timeout(120)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}", $payload);

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
                    return ['success' => true, 'data' => $extracted];
                } else {
                    $errorMsg = "Gagal memproses JSON dari Gemini.";
                    Log::error("Failed to parse Gemini JSON output: " . json_last_error_msg(), ['output' => $text]);
                    return ['success' => false, 'error' => $errorMsg];
                }
            } else {
                $body = $response->json();
                $errorMsg = "API Error: " . ($body['error']['message'] ?? 'Unknown error');
                if ($response->status() == 503) {
                    $errorMsg = "Google Gemini sedang sibuk/overload (503). Silakan coba lagi beberapa saat.";
                }
                Log::error("Gemini API Error: " . $response->body());
                return ['success' => false, 'error' => $errorMsg];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Gemini OCR Timeout: " . $e->getMessage());
            return ['success' => false, 'error' => 'Koneksi ke Google Gemini Timeout (terlalu lama).'];
        } catch (\Exception $e) {
            Log::error("Gemini OCR Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Returns a Filament Form Action for Auto-Fill via AI with Hybrid Pipeline.
     */
    public static function getAutoFillAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('autofill')
            ->label('Auto-Fill via AI')
            ->icon('heroicon-m-sparkles')
            ->requiresConfirmation()
            ->modalHeading('Ekstrak Data Otomatis')
            ->modalDescription('Sistem akan membaca dokumen secara lokal (Tesseract OCR) dan memetakan data secara otomatis melalui AI.')
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
                
                \Filament\Notifications\Notification::make()->title('Memproses dokumen...')->info()->send();
                
                $service = new self();
                $filenameForHint = '';
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $filenameForHint = $file->getClientOriginalName();
                } else if (is_string($file)) {
                    $filenameForHint = basename($file);
                }
                
                // 1. TAHAP 1: Coba Ekstraksi Teks Lokal Terlebih Dahulu (Tesseract / pdftotext)
                $tesseractService = new \App\Services\TesseractOcrService();
                $localResult = $tesseractService->process($content, $filenameForHint);

                $result = null;
                $methodUsed = '';

                if ($localResult['success'] && !$localResult['is_struggling'] && !empty($localResult['text'])) {
                    Log::info("Hybrid OCR: Teks berhasil diekstrak lokal via [{$localResult['source']}]. Meneruskan teks ke Gemini text-prompt.");
                    $result = $service->extractFromRawText($localResult['text'], $filenameForHint, $localResult['source']);
                    
                    if ($result['success']) {
                        $methodUsed = 'hybrid';
                    } else {
                        Log::warning("Hybrid OCR: Gemini gagal memetakan teks lokal, beralih ke Gemini Multimodal PDF.");
                    }
                } else {
                    Log::info("Hybrid OCR: Tesseract kesusahan atau nonaktif (" . ($localResult['reason'] ?? 'Tidak ada teks') . "). Beralih ke Gemini Multimodal PDF.");
                }

                // 2. TAHAP 2: Fallback ke Gemini Multimodal PDF jika tahap lokal tidak digunakan atau gagal
                if (!$result || !$result['success']) {
                    $result = $service->extractFromPdfContent($content, $filenameForHint);
                    if ($result['success']) {
                        $methodUsed = 'gemini_multimodal';
                    }
                }
                
                if ($result && $result['success']) {
                    $data = $result['data'];
                    if (!empty($data['nomor_dokumen_polinema'])) $set('nomor_dokumen_polinema', $data['nomor_dokumen_polinema']);
                    if (!empty($data['nomor_dokumen_mitra'])) $set('nomor_dokumen_mitra', $data['nomor_dokumen_mitra']);
                    if (!empty($data['tanggal_awal'])) $set('tanggal_awal', $data['tanggal_awal']);
                    if (!empty($data['tanggal_akhir'])) $set('tanggal_akhir', $data['tanggal_akhir']);
                    if (!empty($data['judul'])) $set('judul', $data['judul']);
                    if (!empty($data['link_laporan_kegiatan'])) $set('link_laporan_kegiatan', $data['link_laporan_kegiatan']);
                    if (!empty($data['bidang_id'])) $set('bidang_id', $data['bidang_id']);
                    if (!empty($data['jenis_dokumen_id'])) $set('jenis_dokumen_id', $data['jenis_dokumen_id']);
                    if (!empty($data['jenis'])) $set('jenis', $data['jenis']);
                    
                    $extractedNegaraId = null;
                    $extractedProvinsiId = null;
                    $extractedKotaId = null;

                    if (!empty($data['nama_negara'])) {
                        $searchNegara = $data['nama_negara'];
                        
                        // Map common English country names to Indonesian to match database records
                        $aliases = [
                            'china' => 'cina',
                            'prc' => 'cina',
                            'usa' => 'amerika serikat',
                            'united states' => 'amerika serikat',
                            'uk' => 'inggris',
                            'united kingdom' => 'inggris'
                        ];
                        
                        $lowerSearch = strtolower(trim($searchNegara));
                        if (isset($aliases[$lowerSearch])) {
                            $searchNegara = $aliases[$lowerSearch];
                        }

                        $negara = \App\Models\Negara::query()
                            ->where('nama_negara', 'like', '%' . $searchNegara . '%')
                            ->orWhere('nama_negara', 'like', '%' . $data['nama_negara'] . '%')
                            ->first();
                            
                        if ($negara) {
                            $extractedNegaraId = $negara->id;
                            try { $set('usulan_negara_id', $negara->id); } catch (\Exception $e) {}
                        }
                    }

                    if (!empty($data['nama_provinsi'])) {
                        $provinsi = \App\Models\MasterProvinsi::query()->where('nama_provinsi', 'like', '%' . $data['nama_provinsi'] . '%')->first();
                        if ($provinsi) {
                            $extractedProvinsiId = $provinsi->id;
                            $set('provinsi_id', $provinsi->id);
                            
                            if (!empty($data['nama_kota'])) {
                                $kota = \App\Models\MasterKota::query()->where('provinsi_id', $provinsi->id)
                                    ->where('nama_kota', 'like', '%' . $data['nama_kota'] . '%')
                                    ->first();
                                if ($kota) {
                                    $extractedKotaId = $kota->id;
                                    $set('kota_id', $kota->id);
                                }
                            }
                        }
                    } elseif (!empty($data['nama_kota'])) {
                        $kota = \App\Models\MasterKota::query()->where('nama_kota', 'like', '%' . $data['nama_kota'] . '%')->first();
                        if ($kota) {
                            $extractedKotaId = $kota->id;
                            $set('kota_id', $kota->id);
                            if ($kota->provinsi_id) {
                                $extractedProvinsiId = $kota->provinsi_id;
                                $set('provinsi_id', $kota->provinsi_id);
                            }
                        }
                    }

                    if (!empty($data['nama_mitra'])) {
                        $normalizedInput = trim(str_ireplace([' & ', ' dan ', ' pt ', ' cv '], ' ', ' ' . $data['nama_mitra'] . ' '));
                        $mitra = \App\Models\Mitra::query()->where('nama_mitra', 'like', '%' . $data['nama_mitra'] . '%')->first();
                        
                        if (!$mitra) {
                            $allMitras = \App\Models\Mitra::select('id', 'nama_mitra')->get();
                            $bestMatch = null;
                            $highestSimilarity = 0;
                            
                            $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower(str_ireplace([' & ', ' dan ', 'pt ', 'cv ', 'universitas ', 'institut ', 'politeknik '], '', $data['nama_mitra'])));

                            if (strlen($cleanInput) > 3) {
                                foreach ($allMitras as $m) {
                                    $cleanDb = preg_replace('/[^a-z0-9]/', '', strtolower(str_ireplace([' & ', ' dan ', 'pt ', 'cv ', 'universitas ', 'institut ', 'politeknik '], '', $m->nama_mitra)));
                                    
                                    if (strlen($cleanDb) > 3) {
                                        preg_match_all('/\d+/', $cleanInput, $inputNums);
                                        preg_match_all('/\d+/', $cleanDb, $dbNums);
                                        
                                        if (!empty($inputNums[0]) || !empty($dbNums[0])) {
                                            if ($inputNums[0] !== $dbNums[0]) {
                                                continue;
                                            }
                                        }

                                        similar_text($cleanInput, $cleanDb, $percent);
                                        if ($percent > $highestSimilarity) {
                                            $highestSimilarity = $percent;
                                            $bestMatch = $m;
                                        }
                                    }
                                }

                                if ($highestSimilarity >= 85 && $bestMatch) {
                                    $mitra = \App\Models\Mitra::find($bestMatch->id);
                                }
                            }
                        }

                        if (!$mitra) {
                            $mitra = \App\Models\Mitra::create([
                                'nama_mitra' => $data['nama_mitra'],
                                'alamat' => $data['alamat_mitra'] ?? null,
                                'email' => $data['email_mitra'] ?? null,
                                'telepon' => $data['telepon_mitra'] ?? null,
                                'negara_id' => $extractedNegaraId,
                                'provinsi_id' => $extractedProvinsiId,
                                'kota_id' => $extractedKotaId,
                                'kategori_id' => $data['kategori_id'] ?? null,
                            ]);
                            \Filament\Notifications\Notification::make()->title('Mitra baru ditambahkan secara otomatis: ' . $mitra->nama_mitra)->success()->send();
                        }

                        if ($mitra) {
                            $set('mitra_id', $mitra->id);
                            
                            $parentDoc = \App\Models\Kerjasama::query()->where('mitra_id', $mitra->id)
                                ->whereIn('jenis_dokumen_id', [1, 3, 4])
                                ->latest()
                                ->first();
                                
                            if ($parentDoc) {
                                $set('parent_id', $parentDoc->id);
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

                    // Tampilkan notifikasi keberhasilan yang informatif sesuai metode yang digunakan
                    if ($methodUsed === 'hybrid') {
                        $sourceLabel = ($localResult['source'] === 'pdftotext_digital') ? 'Digital PDF' : 'Tesseract OCR';
                        \Filament\Notifications\Notification::make()
                            ->title('Auto-Fill Berhasil via Hybrid OCR!')
                            ->body("Teks diekstrak cepat via {$sourceLabel} & dipetakan otomatis oleh Gemini.")
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Auto-Fill Berhasil via Gemini Multimodal!')
                            ->body('Dokumen diproses penuh melalui analisis dokumen AI.')
                            ->success()
                            ->send();
                    }
                } else {
                    \Filament\Notifications\Notification::make()->title('Gagal mengekstrak data')->body($result['error'] ?? 'Terjadi kesalahan')->danger()->send();
                }
            });
    }
}
