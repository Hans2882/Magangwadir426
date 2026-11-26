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
        $jurusanList = \App\Models\MasterJurusan::pluck('nama_jurusan', 'id')
            ->map(fn ($name) => trim((string) $name))
            ->toArray();
        $prodiList = \App\Models\MasterProgramStudi::pluck('nama_prodi', 'id')
            ->map(fn ($name) => trim((string) $name))
            ->toArray();
        
        $kategoriString = json_encode($kategoriList, JSON_UNESCAPED_UNICODE);
        $bidangString = json_encode($bidangList, JSON_UNESCAPED_UNICODE);
        $jenisDokumenString = json_encode($jenisDokumenList, JSON_UNESCAPED_UNICODE);
        $jurusanString = json_encode($jurusanList, JSON_UNESCAPED_UNICODE);
        $prodiString = json_encode($prodiList, JSON_UNESCAPED_UNICODE);

        $prompt = "You are a highly accurate data extraction assistant. Analyze the attached Indonesian cooperation document (MoU, MoA, IA, PKS, or Laporan Kegiatan/Case Study). Extract the following information and return it strictly as a single, valid JSON object, with no markdown formatting, no preamble, and no extra braces.\n"
                . "Use exactly these keys:\n"
                . "- nomor_dokumen_polinema (String, the first document number at the top, usually containing PL2, or the 'Surat Tugas' number for activity reports)\n"
                . "- nomor_dokumen_mitra (String, the second document number at the top, belonging to the partner. Leave blank for activity reports)\n"
                . "- tanggal_awal (Date in YYYY-MM-DD format, the start date, signing date, or the date of the activity)\n"
                . "- tanggal_akhir (Date in YYYY-MM-DD format. FIRST, search the document thoroughly for any explicit end date, expiration date, or duration/validity clause, e.g. 'berlaku sampai...', 'sampai dengan...', 'berlaku hingga...', 'berlaku selama X tahun/bulan', or 'jangka waktu X tahun'. If the document explicitly states an end date or a duration in years/months, calculate that exact date from tanggal_awal (e.g. if start date is 2026-10-27 and duration is 'jangka waktu 5 (lima) tahun', tanggal_akhir is 2031-10-27). If and only if the document contains NO mention of an end date or validity duration whatsoever: for IA documents, fallback to 1 year after tanggal_awal; for other documents, return null)\n"
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
                . "- jenis_dokumen_id (Integer or null, classify the document type strictly based on title, heading, and content. Use ONLY one of the keys from this exact mapping: $jenisDokumenString.\n"
                . "  Classification guide:\n"
                . "  * 1 (MoU) if titled 'NOTA KESEPAHAMAN' or 'MEMORANDUM OF UNDERSTANDING'.\n"
                . "  * 7 (LoI) if titled 'LETTER OF INTENT' or 'SURAT PERNYATAAN KEHENDAK'.\n"
                . "  * 3 (PKS) if titled 'PERJANJIAN KERJA SAMA', 'PERJANJIAN KERJASAMA', or 'SURAT PERJANJIAN'.\n"
                . "  * 2 (MoA) if titled 'MEMORANDUM OF AGREEMENT' or 'MOA'.\n"
                . "  * 5 (SPK) if titled 'SURAT PERJANJIAN KERJA' or 'SPK'.\n"
                . "  * 6 (LoC) if titled 'LETTER OF COLLABORATION'.\n"
                . "  * 4 (IA) if titled 'IMPLEMENTATION ARRANGEMENT', 'IA', or 'PENGATURAN PELAKSANAAN'.)\n"
                . "- detected_category (String, exactly 'MoU', 'PKS', or 'IA' representing the main category)\n"
                . "- detected_reason (String, 1 concise sentence in Indonesian explaining why this document type was chosen)\n"
                . "- jenis (String, guess the scope or Cakupan (DN/LN) based on the partner's country. Must be EXACTLY 'Dalam Negeri' if the partner is from Indonesia, or 'Luar Negeri' if the partner is from outside Indonesia)\n"
                . "- link_laporan_kegiatan (String, a URL or link mentioned in the document referring to an activity report, Google Drive, or evidence link, otherwise null)\n"
                . "- prodi_ids (Array of Integers, match every 'Program Studi' or 'Prodi' mentioned anywhere in the document—including title, TENTANG section, or Korespondensi—strictly to keys from this exact mapping: $prodiString. Note: if the document mentions 'Kampus Kediri', 'Kampus Lumajang', or 'Kampus Pamekasan', match to the corresponding entry ending with 'Kediri', 'Lumajang', or 'Pamekasan' without the word 'Kampus'. Standardize D-III/D3 and D-IV/D4 -> 'Sarjana Terapan'. Return [] if none mentioned)\n"
                . "- jurusan_ids (Array of Integers, match any 'Jurusan' mentioned in the document or directly implied by the matched prodis strictly to keys from this exact mapping: $jurusanString. Return [] if none)\n"
                . "- prodis (Array of Strings, fallback list of 'Program Studi' / 'Prodi' names mentioned in the document without the word 'Program Studi'/'Prodi'/'Kampus', standardizing 'D-III' -> 'D3' and 'D-IV'/'D4' -> 'Sarjana Terapan')\n"
                . "- jurusans (Array of Strings, fallback list of 'Jurusan' names mentioned in the document)\n";

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
            ->retry(2, 1000, function ($exception, $request) {
                // Hanya retry jika errornya bukan timeout koneksi (agar total waktu tidak melebihi batas 100s server)
                return !($exception instanceof \Illuminate\Http\Client\ConnectionException);
            }, throw: false)
            ->timeout(75)
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
    public static function getAutoFillAction(?string $documentType = null): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('autofill')
            ->label('Isi Otomatis')
            ->icon('heroicon-m-document-text')
            ->requiresConfirmation()
            ->modalHeading('Baca Dokumen Otomatis')
            ->modalDescription('Sistem akan membaca isi berkas yang diunggah dan mengisi kolom formulir secara otomatis.')
            ->modalSubmitActionLabel('Mulai Baca Berkas')
            ->action(function ($get, $set, $livewire = null) use ($documentType) {
                @set_time_limit(100);

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

                    // Konteks form: apakah form secara spesifik khusus untuk IA (misal IaResource)
                    $isIaContext = ($documentType !== null && strtoupper($documentType) === 'IA');
                    if (!$isIaContext && $livewire && str_contains(get_class($livewire), 'IaResource')) {
                        $isIaContext = true;
                    }

                    // Deteksi dari hasil ekstraksi Gemini
                    $detectedDocId = !empty($data['jenis_dokumen_id']) ? (int) $data['jenis_dokumen_id'] : null;
                    $detectedCategory = !empty($data['detected_category']) ? trim((string) $data['detected_category']) : null;
                    $isDetectedAsIa = ($detectedDocId === 4) || (strtoupper((string) $detectedCategory) === 'IA');

                    if (!empty($data['nomor_dokumen_polinema'])) $set('nomor_dokumen_polinema', $data['nomor_dokumen_polinema']);
                    if (!empty($data['nomor_dokumen_mitra'])) $set('nomor_dokumen_mitra', $data['nomor_dokumen_mitra']);

                    // Pemetaan Tanggal Awal & Tanggal Akhir
                    if (!empty($data['tanggal_awal'])) {
                        $parsedAwal = null;
                        try {
                            $rawAwal = trim((string) $data['tanggal_awal']);
                            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $rawAwal, $m)) {
                                $parsedAwal = \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
                            } elseif (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $rawAwal, $m)) {
                                $parsedAwal = \Carbon\Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
                            } else {
                                $parsedAwal = \Carbon\Carbon::parse($rawAwal);
                            }
                            $set('tanggal_awal', $parsedAwal->format('Y-m-d'));
                        } catch (\Throwable $e) {
                            $set('tanggal_awal', $data['tanggal_awal']);
                        }

                    }

                    // Tanggal Akhir: Cari & utamakan tanggal akhir yang tertera di dokumen terlebih dahulu
                    if (!empty($data['tanggal_akhir'])) {
                        try {
                            $rawAkhir = trim((string) $data['tanggal_akhir']);
                            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $rawAkhir, $m)) {
                                $parsedAkhir = \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
                            } elseif (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $rawAkhir, $m)) {
                                $parsedAkhir = \Carbon\Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
                            } else {
                                $parsedAkhir = \Carbon\Carbon::parse($rawAkhir);
                            }
                            $set('tanggal_akhir', $parsedAkhir->format('Y-m-d'));
                        } catch (\Throwable $e) {
                            $set('tanggal_akhir', $data['tanggal_akhir']);
                        }
                    } elseif ($isDetectedAsIa && !empty($data['tanggal_awal'])) {
                        // Jika di dokumen IA tidak tertera tanggal akhir, baru gunakan fallback standar 1 tahun
                        if ($parsedAwal) {
                            $set('tanggal_akhir', $parsedAwal->copy()->addYear()->format('Y-m-d'));
                        } else {
                            try {
                                $set('tanggal_akhir', \Carbon\Carbon::parse($data['tanggal_awal'])->addYear()->format('Y-m-d'));
                            } catch (\Throwable $e) {}
                        }
                    }

                    if (!empty($data['judul'])) $set('judul', $data['judul']);
                    if (!empty($data['link_laporan_kegiatan'])) $set('link_laporan_kegiatan', $data['link_laporan_kegiatan']);
                    if (!empty($data['bidang_id'])) $set('bidang_id', $data['bidang_id']);

                    $user = \Illuminate\Support\Facades\Auth::user();
                    $isDosenUser = (bool) $user?->isDosen();

                    // Tetapkan ai_detected_category sesuai deteksi dokumen yang sebenarnya
                    $actualDetectedCategory = $detectedCategory ?? match ($detectedDocId) {
                        1, 7 => 'MoU',
                        2, 3, 5, 6 => 'PKS',
                        4 => 'IA',
                        default => ($isDetectedAsIa ? 'IA' : 'Dokumen'),
                    };
                    $set('ai_detected_category', $actualDetectedCategory);

                    if (!empty($data['detected_reason'])) {
                        $set('ai_detected_reason', $data['detected_reason']);
                    }

                    // Tentukan nilai field jenis_dokumen_id pada formulir
                    if ($isIaContext || $isDosenUser) {
                        // Form IA atau role Dosen: opsi formulir dikunci pada IA (4)
                        $set('jenis_dokumen_id', 4);
                    } elseif ($detectedDocId) {
                        // Form umum: sesuaikan jenis_dokumen_id dengan hasil deteksi berkas
                        $set('jenis_dokumen_id', $detectedDocId);
                    }

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
                            
                            $resolvedDocId = ($isIaContext || $isDosenUser)
                            ? 4
                            : ($detectedDocId ?? (int) ($get('jenis_dokumen_id') ?? 1));

                            // Jika PKS/MoA/SPK/LoC (2,3,5,6) atau IA (4), cari referensi MoU aktif mitra tersebut
                            if (in_array($resolvedDocId, [2, 3, 4, 5, 6])) {
                                $latestMou = \App\Models\Kerjasama::query()
                                    ->where('mitra_id', $mitra->id)
                                    ->whereIn('jenis_dokumen_id', [1, 7])
                                    ->latest('tanggal_awal')
                                    ->first();
                                    
                                if ($latestMou) {
                                    $set('parent_id', $latestMou->id);
                                }
                            }

                            // Jika IA (4), cari juga referensi PKS aktif mitra tersebut
                            if ($resolvedDocId === 4) {
                                $latestPks = \App\Models\Kerjasama::query()
                                    ->where('mitra_id', $mitra->id)
                                    ->whereIn('jenis_dokumen_id', [2, 3, 5, 6])
                                    ->latest('tanggal_awal')
                                    ->first();

                                if ($latestPks) {
                                    $set('pks_id', $latestPks->id);
                                }
                            }
                        }
                    }

                    $resolvedUnits = $service->resolveJurusanAndProdiIds($data);
                    $jurusanIds = $resolvedUnits['jurusan_ids'];
                    $prodiIds = $resolvedUnits['prodi_ids'];

                    // Set jurusans terlebih dahulu agar filter opsi relasi prodis sudah sinkron
                    if (!empty($jurusanIds)) {
                        $set('jurusans', $jurusanIds);
                    }
                    if (!empty($prodiIds)) {
                        $set('prodis', $prodiIds);
                    }

                    // Tampilkan notifikasi keberhasilan atau peringatan jika Dosen/Form IA mengunggah selain IA
                    if ($isDosenUser && !$isDetectedAsIa) {
                        \Filament\Notifications\Notification::make()
                            ->title('Perhatian: Berkas Terdeteksi ' . $actualDetectedCategory)
                            ->body("Berkas yang Anda unggah terdeteksi sebagai dokumen {$actualDetectedCategory}. Role Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).")
                            ->warning()
                            ->persistent()
                            ->send();
                    } elseif ($isIaContext && !$isDetectedAsIa) {
                        \Filament\Notifications\Notification::make()
                            ->title('Perhatian: Berkas Terdeteksi ' . $actualDetectedCategory)
                            ->body("Formulir ini khusus untuk dokumen IA, namun berkas yang diunggah terdeteksi sebagai dokumen {$actualDetectedCategory}.")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Data Dokumen Berhasil Dibaca')
                            ->body('Informasi dokumen telah berhasil dimasukkan ke dalam formulir.')
                            ->success()
                            ->send();
                    }
                } else {
                    \Filament\Notifications\Notification::make()->title('Gagal mengekstrak data')->body($result['error'] ?? 'Terjadi kesalahan')->danger()->send();
                }
            });
    }

    /**
     * Resolve MasterJurusan and MasterProgramStudi IDs from Gemini extraction payload.
     *
     * Handles campus naming variations (e.g. "Kampus Kediri" -> "Kediri"), degree aliases
     * (D-IV/D4 -> Sarjana Terapan, D-III -> D3), and automatically derives jurusan_id from prodis.
     *
     * @return array{jurusan_ids: array<int, int>, prodi_ids: array<int, int>}
     */
    public function resolveJurusanAndProdiIds(array $data): array
    {
        $allJurusans = \App\Models\MasterJurusan::all();
        $allProdis = \App\Models\MasterProgramStudi::all();

        $jurusanById = $allJurusans->keyBy('id');
        $prodiById = $allProdis->keyBy('id');

        $jurusanIds = [];
        $prodiIds = [];

        // 1. Direct prodi_ids from Gemini
        if (!empty($data['prodi_ids']) && is_array($data['prodi_ids'])) {
            foreach ($data['prodi_ids'] as $rawId) {
                $id = (int) $rawId;
                if ($prodi = $prodiById->get($id)) {
                    $prodiIds[] = $prodi->id;
                    if ($prodi->jurusan_id) {
                        $jurusanIds[] = (int) $prodi->jurusan_id;
                    }
                }
            }
        }

        // 2. Fallback string matching on prodis (supports Kampus/PSDKU suffixes & degree normalization)
        if (!empty($data['prodis']) && is_array($data['prodis'])) {
            foreach ($data['prodis'] as $rawProdiName) {
                $matched = $this->matchProgramStudi((string) $rawProdiName, $allProdis);
                if ($matched) {
                    $prodiIds[] = (int) $matched->id;
                    if ($matched->jurusan_id) {
                        $jurusanIds[] = (int) $matched->jurusan_id;
                    }
                }
            }
        }

        // 3. Direct jurusan_ids from Gemini
        if (!empty($data['jurusan_ids']) && is_array($data['jurusan_ids'])) {
            foreach ($data['jurusan_ids'] as $rawId) {
                $id = (int) $rawId;
                if ($jurusanById->has($id)) {
                    $jurusanIds[] = $id;
                }
            }
        }

        // 4. Fallback string matching on jurusans
        if (!empty($data['jurusans']) && is_array($data['jurusans'])) {
            foreach ($data['jurusans'] as $rawJurusanName) {
                $matched = $this->matchJurusan((string) $rawJurusanName, $allJurusans);
                if ($matched) {
                    $jurusanIds[] = (int) $matched->id;
                }
            }
        }

        return [
            'jurusan_ids' => array_values(array_unique(array_map('intval', $jurusanIds))),
            'prodi_ids' => array_values(array_unique(array_map('intval', $prodiIds))),
        ];
    }

    /**
     * Match an extracted study program string against MasterProgramStudi records.
     */
    private function matchProgramStudi(string $rawName, \Illuminate\Support\Collection $allProdis): ?\App\Models\MasterProgramStudi
    {
        $normInput = $this->normalizeAcademicUnitName($rawName);
        if ($normInput === '') {
            return null;
        }

        $inputCampus = $this->detectBranchCampus($normInput);
        $inputDegree = $this->detectDegreePrefix($normInput);

        $candidates = $allProdis->filter(function ($prodi) use ($inputCampus) {
            $normDb = $this->normalizeAcademicUnitName((string) $prodi->nama_prodi);
            $dbCampus = $this->detectBranchCampus($normDb);

            // Prevent cross-matching main campus with branch campus (e.g. Kediri/Lumajang/Pamekasan)
            return $inputCampus === $dbCampus;
        });

        if ($candidates->isEmpty()) {
            $candidates = $allProdis;
        }

        // Narrow by degree prefix when both input and at least one candidate specify the same degree
        if ($inputDegree !== null) {
            $sameDegree = $candidates->filter(
                fn ($p) => $this->detectDegreePrefix($this->normalizeAcademicUnitName((string) $p->nama_prodi)) === $inputDegree
            );
            if ($sameDegree->isNotEmpty()) {
                $candidates = $sameDegree;
            }
        }

        // Exact normalized match
        foreach ($candidates as $prodi) {
            if ($this->normalizeAcademicUnitName((string) $prodi->nama_prodi) === $normInput) {
                return $prodi;
            }
        }

        // Best containment or fuzzy match
        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($candidates as $prodi) {
            $normDb = $this->normalizeAcademicUnitName((string) $prodi->nama_prodi);
            if ($normDb === '') {
                continue;
            }

            if (str_contains($normDb, $normInput) || str_contains($normInput, $normDb)) {
                $score = 90.0 + (min(strlen($normInput), strlen($normDb)) / max(strlen($normInput), strlen($normDb))) * 10.0;
            } else {
                similar_text($normInput, $normDb, $score);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $prodi;
            }
        }

        return $bestScore >= 78.0 ? $bestMatch : null;
    }

    /**
     * Match an extracted department string against MasterJurusan records.
     */
    private function matchJurusan(string $rawName, \Illuminate\Support\Collection $allJurusans): ?\App\Models\MasterJurusan
    {
        $normInput = $this->normalizeAcademicUnitName($rawName, stripJurusanWord: true);
        if ($normInput === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($allJurusans as $jurusan) {
            $normDb = $this->normalizeAcademicUnitName((string) $jurusan->nama_jurusan, stripJurusanWord: true);
            if ($normDb === $normInput) {
                return $jurusan;
            }

            if (str_contains($normDb, $normInput) || str_contains($normInput, $normDb)) {
                $score = 92.0;
            } else {
                similar_text($normInput, $normDb, $score);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $jurusan;
            }
        }

        return $bestScore >= 80.0 ? $bestMatch : null;
    }

    /**
     * Normalize Indonesian academic unit / program names for consistent lookup.
     */
    private function normalizeAcademicUnitName(string $name, bool $stripJurusanWord = false): string
    {
        $text = strtolower(trim($name));

        $noisePhrases = [
            'program studi',
            'prodi',
            'kampus utama',
            'kampus cabang',
            'kampus',
            'psdku',
            'pdd',
            'cabang',
            'politeknik negeri malang',
            'polinema',
        ];
        if ($stripJurusanWord) {
            $noisePhrases[] = 'jurusan';
        }

        foreach ($noisePhrases as $phrase) {
            $text = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ', $text);
        }

        $replacements = [
            '/\bdiploma\s*(iv|4)\b/u' => 'sarjana terapan',
            '/\bd[\s\-]*(iv|4)\b/u' => 'sarjana terapan',
            '/\bs[\.\s]*tr\.?\b/u' => 'sarjana terapan',
            '/\bdiploma\s*(iii|3)\b/u' => 'd3',
            '/\bd[\s\-]*(iii|3)\b/u' => 'd3',
            '/\bdiploma\s*(ii|2)\b/u' => 'd2',
            '/\bd[\s\-]*(ii|2)\b/u' => 'd2',
            '/\bdiploma\s*(i|1)\b/u' => 'd1',
            '/\bd[\s\-]*(i|1)\b/u' => 'd1',
            '/\bs[\s\-]*(iii|3)\b/u' => 's3',
            '/\bs[\s\-]*(ii|2)\b/u' => 's2',
            '/\bs[\s\-]*(i|1)\b/u' => 's1',
            '/&/u' => ' dan ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function detectBranchCampus(string $normalizedName): ?string
    {
        foreach (['kediri', 'lumajang', 'pamekasan'] as $campus) {
            if (preg_match('/\b' . $campus . '\b/u', $normalizedName)) {
                return $campus;
            }
        }

        return null;
    }

    private function detectDegreePrefix(string $normalizedName): ?string
    {
        foreach (['sarjana terapan', 'd3', 'd2', 'd1', 's3', 's2', 's1'] as $degree) {
            if (preg_match('/\b' . preg_quote($degree, '/') . '\b/u', $normalizedName)) {
                return $degree;
            }
        }

        return null;
    }
}
