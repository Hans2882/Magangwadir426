<?php

namespace App\Services;

use App\Models\JenisDokumen;
use App\Models\Kerjasama;
use App\Models\MasterJurusan;
use App\Models\MasterKegiatan;
use App\Models\MasterKota;
use App\Models\MasterMitraIku;
use App\Models\MasterProgramStudi;
use App\Models\MasterProvinsi;
use App\Models\Mitra;
use App\Models\Negara;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiPressReleaseService
{
    /**
     * Fetches and sanitizes article text from a public web URL.
     */
    public function fetchArticleFromUrl(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Format URL tidak valid. Pastikan diawali dengan http:// atau https://',
            ];
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ])
            ->timeout(20)
            ->get($url);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "Gagal mengakses link berita (HTTP status {$response->status()}). Silakan gunakan opsi tempel teks artikel secara manual.",
                ];
            }

            $html = $response->body();

            // Extract page title hint
            $titleHint = '';
            if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
                $titleHint = trim(html_entity_decode($matches[1]));
            } elseif (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                $titleHint = trim(html_entity_decode($matches[1]));
            }

            // Remove scripts, styles, and navigational elements
            $cleanedHtml = preg_replace([
                '/<script\b[^>]*>(.*?)<\/script>/is',
                '/<style\b[^>]*>(.*?)<\/style>/is',
                '/<noscript\b[^>]*>(.*?)<\/noscript>/is',
                '/<nav\b[^>]*>(.*?)<\/nav>/is',
                '/<header\b[^>]*>(.*?)<\/header>/is',
                '/<footer\b[^>]*>(.*?)<\/footer>/is',
                '/<svg\b[^>]*>(.*?)<\/svg>/is',
                '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            ], ' ', $html);

            $cleanText = strip_tags($cleanedHtml);
            $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanText = preg_replace('/\s+/', ' ', $cleanText);
            $cleanText = trim($cleanText);

            if (empty($cleanText) || strlen($cleanText) < 50) {
                return [
                    'success' => false,
                    'error' => 'Konten teks dari tautan terlalu pendek atau tidak dapat dibaca. Silakan tempel teks berita secara manual.',
                ];
            }

            // Keep up to 15,000 characters for optimal AI prompt processing
            $cleanText = mb_substr($cleanText, 0, 15000);

            return [
                'success' => true,
                'text' => $cleanText,
                'title_hint' => $titleHint,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Press Release Scraping Connection Error: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => 'Koneksi ke website berita timeout atau gagal. Silakan gunakan opsi tempel teks artikel berita secara manual.',
            ];
        } catch (\Exception $e) {
            Log::error("Press Release Scraping Exception: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => 'Gagal membaca tautan berita: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extracts structured information from news article text using Google Gemini 3.6 Flash.
     */
    public function extractFromText(string $articleText, string $titleHint = ''): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            Log::error('Gemini API Key is missing.');
            return ['success' => false, 'error' => 'API Key Gemini belum diatur di server (.env).'];
        }

        if (empty($articleText)) {
            return ['success' => false, 'error' => 'Teks artikel berita kosong.'];
        }

        $kategoriList = MasterMitraIku::pluck('kategori', 'id')->toArray();
        $bidangList = MasterKegiatan::pluck('bidang_kerjasama', 'id')->toArray();
        $jenisDokumenList = JenisDokumen::pluck('nama', 'id')->toArray();

        $kategoriString = json_encode($kategoriList);
        $bidangString = json_encode($bidangList);
        $jenisDokumenString = json_encode($jenisDokumenList);

        $prompt = "You are a professional news and institutional partnership data extraction assistant for Politeknik Negeri Malang (Polinema).\n"
            . "Analyze the attached Indonesian news article, press release, or media publication. Extract cooperation, event, or institutional activity details.\n"
            . "Return strictly a single, valid JSON object with no markdown backticks, no preamble, and no trailing characters.\n\n"
            . "Use exactly these keys:\n"
            . "- judul (String, the news headline or event title. Be concise and descriptive)\n"
            . "- tanggal_awal (Date in YYYY-MM-DD format, the publication date or the date the activity occurred. If only year/month is mentioned, pick the best estimate)\n"
            . "- tanggal_akhir (Date in YYYY-MM-DD format, if it was a multi-day event or duration is stated, otherwise null)\n"
            . "- nama_mitra (String or null, the external partner organization, company, university, or government body collaborating with Polinema. If the article is purely internal Polinema news without an external partner, return null)\n"
            . "- alamat_mitra (String or null, the partner address if mentioned or widely known)\n"
            . "- nama_negara (String, guess the partner country or event country, e.g. 'Indonesia')\n"
            . "- nama_provinsi (String or null, the province of the partner or event location)\n"
            . "- nama_kota (String or null, the city of the partner or event location)\n"
            . "- kategori_id (Integer or null, guess the partner category ID from this exact mapping: $kategoriString. Return null if no external partner)\n"
            . "- bidang_id (Integer or null, guess the collaboration/activity field ID from this exact mapping: $bidangString)\n"
            . "- jenis (String, MUST be exactly 'Dalam Negeri' if domestic/Indonesia, or 'Luar Negeri' if international)\n"
            . "- prodis (Array of Strings, list of Program Studi mentioned in the article. Extract ONLY major name, do not include 'Program Studi' or 'Prodi'. Standardize 'D-IV' or 'D4' to 'Sarjana Terapan', 'D-III' to 'D3', 'S-I' to 'S1')\n"
            . "- jurusans (Array of Strings, list of Jurusan mentioned in the article, e.g. 'Teknologi Informasi', 'Teknik Mesin', 'Teknik Elektro', 'Akuntansi', 'Administrasi Niaga', 'Teknik Sipil', 'Teknik Kimia')\n";

        if (!empty($titleHint)) {
            $prompt .= "\nNote: The page headline/title hint is '{$titleHint}'.\n";
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt . "\n\n=== ARTICLE TEXT ===\n" . $articleText,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'response_mime_type' => 'application/json',
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                $start = strpos($text, '{');
                $end = strrpos($text, '}');
                if ($start !== false && $end !== false && $end >= $start) {
                    $text = substr($text, $start, $end - $start + 1);
                }

                $extracted = json_decode(trim($text), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return ['success' => true, 'data' => $extracted];
                }

                Log::error('Failed to parse Gemini JSON output for Press Release: ' . json_last_error_msg(), ['output' => $text]);
                return ['success' => false, 'error' => 'Gagal memproses JSON dari Google Gemini.'];
            }

            $body = $response->json();
            $errorMsg = 'API Error: ' . ($body['error']['message'] ?? 'Unknown error');
            if ($response->status() == 503) {
                $errorMsg = 'Google Gemini sedang sibuk/overload (503). Silakan coba lagi beberapa saat.';
            }
            Log::error('Gemini API Error (Press Release): ' . $response->body());
            return ['success' => false, 'error' => $errorMsg];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini Press Release Timeout: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Koneksi ke Google Gemini Timeout (terlalu lama).'];
        } catch (\Exception $e) {
            Log::error('Gemini Press Release Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Returns a Filament Form Action for Auto-Fill Press Release via AI.
     */
    public static function getAutoFillAction(): Action
    {
        return Action::make('autofill_press_release')
            ->label('Auto-Fill via AI')
            ->icon('heroicon-m-sparkles')
            ->modalHeading('Auto-Fill Press Release via AI')
            ->modalDescription('Sistem akan membaca isi artikel berita dan mengekstrak judul, tanggal terbit, mitra, serta jurusan/prodi secara otomatis.')
            ->modalSubmitActionLabel('Mulai Ekstrak')
            ->form([
                Forms\Components\Textarea::make('manual_text')
                    ->label('Atau Tempel Teks Artikel Berita (Opsional)')
                    ->placeholder('Tempelkan teks berita di sini jika tautan website terkunci, memerlukan login, atau gagal dijangkau server...')
                    ->rows(6)
                    ->helperText('Kosongkan jika ingin sistem membaca langsung dari Link Press Release yang sudah diisi.'),
            ])
            ->action(function ($get, $set, array $data) {
                $manualText = trim($data['manual_text'] ?? '');
                $url = trim($get('link_press_release') ?? '');

                if (empty($manualText) && empty($url)) {
                    Notification::make()
                        ->title('Input Belum Diisi')
                        ->body('Silakan isi kolom Link Press Release terlebih dahulu atau tempel teks artikel berita pada jendela ini.')
                        ->danger()
                        ->send();
                    return;
                }

                $service = new self();
                $articleText = '';
                $titleHint = '';

                if (!empty($manualText)) {
                    $articleText = $manualText;
                    $titleHint = 'Manual Pasted Article';
                } else {
                    Notification::make()
                        ->title('Mengunduh Halaman Berita...')
                        ->info()
                        ->send();

                    $scrapeResult = $service->fetchArticleFromUrl($url);
                    if (!$scrapeResult['success']) {
                        Notification::make()
                            ->title('Gagal Membaca Link Berita')
                            ->body($scrapeResult['error'])
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    $articleText = $scrapeResult['text'];
                    $titleHint = $scrapeResult['title_hint'] ?? '';
                }

                Notification::make()
                    ->title('Menganalisis Berita via Gemini AI...')
                    ->info()
                    ->send();

                $aiResult = $service->extractFromText($articleText, $titleHint);

                if (!$aiResult['success']) {
                    Notification::make()
                        ->title('Ekstraksi Gagal')
                        ->body($aiResult['error'])
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }

                $extracted = $aiResult['data'];

                // 1. Judul & Tanggal
                if (!empty($extracted['judul'])) {
                    $set('judul', $extracted['judul']);
                }
                if (!empty($extracted['tanggal_awal'])) {
                    $set('tanggal_awal', $extracted['tanggal_awal']);
                }
                if (!empty($extracted['bidang_id'])) {
                    $set('bidang_id', $extracted['bidang_id']);
                }
                if (!empty($extracted['jenis'])) {
                    $set('jenis', $extracted['jenis']);
                }

                // 2. Resolusi Lokasi (Negara, Provinsi, Kota)
                $extractedNegaraId = null;
                $extractedProvinsiId = null;
                $extractedKotaId = null;

                if (!empty($extracted['nama_negara'])) {
                    $searchNegara = $extracted['nama_negara'];
                    $aliases = [
                        'china' => 'cina',
                        'prc' => 'cina',
                        'usa' => 'amerika serikat',
                        'united states' => 'amerika serikat',
                        'uk' => 'inggris',
                        'united kingdom' => 'inggris',
                    ];
                    $lower = strtolower(trim($searchNegara));
                    if (isset($aliases[$lower])) {
                        $searchNegara = $aliases[$lower];
                    }

                    $negara = Negara::query()
                        ->where('nama_negara', 'like', '%' . $searchNegara . '%')
                        ->orWhere('nama_negara', 'like', '%' . $extracted['nama_negara'] . '%')
                        ->first();

                    if ($negara) {
                        $extractedNegaraId = $negara->id;
                    }
                }

                if (!empty($extracted['nama_provinsi'])) {
                    $provinsi = MasterProvinsi::query()
                        ->where('nama_provinsi', 'like', '%' . $extracted['nama_provinsi'] . '%')
                        ->first();
                    if ($provinsi) {
                        $extractedProvinsiId = $provinsi->id;
                        $set('provinsi_id', $provinsi->id);

                        if (!empty($extracted['nama_kota'])) {
                            $kota = MasterKota::query()
                                ->where('provinsi_id', $provinsi->id)
                                ->where('nama_kota', 'like', '%' . $extracted['nama_kota'] . '%')
                                ->first();
                            if ($kota) {
                                $extractedKotaId = $kota->id;
                                $set('kota_id', $kota->id);
                            }
                        }
                    }
                } elseif (!empty($extracted['nama_kota'])) {
                    $kota = MasterKota::query()
                        ->where('nama_kota', 'like', '%' . $extracted['nama_kota'] . '%')
                        ->first();
                    if ($kota) {
                        $extractedKotaId = $kota->id;
                        $set('kota_id', $kota->id);
                        if ($kota->provinsi_id) {
                            $extractedProvinsiId = $kota->provinsi_id;
                            $set('provinsi_id', $kota->provinsi_id);
                        }
                    }
                }

                // 3. Mitra Matching (Direct -> Fuzzy -> Auto-Create)
                if (!empty($extracted['nama_mitra'])) {
                    $mitraName = trim($extracted['nama_mitra']);
                    $mitra = Mitra::query()->where('nama_mitra', 'like', '%' . $mitraName . '%')->first();

                    if (!$mitra) {
                        $allMitras = Mitra::select('id', 'nama_mitra')->get();
                        $bestMatch = null;
                        $highestSimilarity = 0;

                        $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower(str_ireplace([' & ', ' dan ', 'pt ', 'cv ', 'universitas ', 'institut ', 'politeknik '], '', $mitraName)));

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
                                $mitra = Mitra::find($bestMatch->id);
                            }
                        }
                    }

                    // Auto-create new Mitra if external partner name detected
                    if (!$mitra && strlen($mitraName) > 2) {
                        $mitra = Mitra::create([
                            'nama_mitra' => $mitraName,
                            'alamat' => $extracted['alamat_mitra'] ?? null,
                            'negara_id' => $extractedNegaraId,
                            'provinsi_id' => $extractedProvinsiId,
                            'kota_id' => $extractedKotaId,
                            'kategori_id' => $extracted['kategori_id'] ?? null,
                        ]);
                    }

                    if ($mitra) {
                        $set('mitra_id', $mitra->id);

                        // Auto-select latest parent agreement (MoU/PKS/IA)
                        $parentDoc = Kerjasama::query()
                            ->where('mitra_id', $mitra->id)
                            ->whereIn('jenis_dokumen_id', [1, 3, 4])
                            ->latest()
                            ->first();

                        if ($parentDoc) {
                            $set('parent_id', $parentDoc->id);
                        }
                    }
                }

                // 4. Resolusi Jurusan & Program Studi
                $jurusanIds = [];
                if (!empty($extracted['jurusans']) && is_array($extracted['jurusans'])) {
                    foreach ($extracted['jurusans'] as $jurusanName) {
                        $j = MasterJurusan::query()
                            ->where('nama_jurusan', 'like', '%' . trim($jurusanName) . '%')
                            ->first();
                        if ($j && !in_array($j->id, $jurusanIds)) {
                            $jurusanIds[] = $j->id;
                        }
                    }
                }

                if (!empty($extracted['prodis']) && is_array($extracted['prodis'])) {
                    $prodiIds = [];
                    foreach ($extracted['prodis'] as $prodiName) {
                        $searchName = trim(str_ireplace(['Program Studi', 'Prodi'], '', $prodiName));
                        $searchName = str_replace(
                            ['D-IV', 'D4', 'D-III', 'D-II', 'D-I', 'S-I', 'S-II', 'S-III'],
                            ['Sarjana Terapan', 'Sarjana Terapan', 'D3', 'D2', 'D1', 'S1', 'S2', 'S3'],
                            $searchName
                        );

                        $p = MasterProgramStudi::query()
                            ->where('nama_prodi', 'like', '%' . $searchName . '%')
                            ->first();
                        if ($p) {
                            $prodiIds[] = $p->id;
                            if ($p->jurusan_id && !in_array($p->jurusan_id, $jurusanIds)) {
                                $jurusanIds[] = $p->jurusan_id;
                            }
                        }
                    }
                    if (!empty($prodiIds)) {
                        $set('prodis', $prodiIds);
                    }
                }

                if (!empty($jurusanIds)) {
                    $set('jurusans', $jurusanIds);
                }

                Notification::make()
                    ->title('Auto-Fill Berita Berhasil!')
                    ->body('Data artikel berita berhasil diekstrak dan formulir telah terisi otomatis.')
                    ->success()
                    ->send();
            });
    }
}

