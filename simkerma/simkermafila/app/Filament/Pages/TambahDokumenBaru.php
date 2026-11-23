<?php

namespace App\Filament\Pages;

use App\Filament\Resources\IaResource;
use App\Filament\Resources\MouResource;
use App\Filament\Resources\PksSpkResource;
use App\Filament\Resources\Traits\HasKerjasamaLocationFormSchema;
use App\Filament\Resources\Traits\HasMitraFormSchema;
use App\Models\Kerjasama;
use App\Models\Mitra;
use App\Services\GeminiOcrService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TambahDokumenBaru extends Page implements HasForms
{
    use InteractsWithForms;
    use HasMitraFormSchema;
    use HasKerjasamaLocationFormSchema;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-plus';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Tambah Dokumen Baru';

    protected static ?string $title = 'Tambah Dokumen Kerjasama';

    public function getSubheading(): ?string
    {
        $user = Auth::user();
        if ($user?->isDosen()) {
            return 'Khusus Dosen, pengunggahan dokumen difokuskan untuk Implementation Arrangement (IA). Formulir akan terisi otomatis dari berkas yang Anda pilih.';
        }

        return 'Unggah berkas kerja sama (MoU, PKS, atau IA). Formulir akan terisi otomatis berdasarkan berkas yang Anda pilih.';
    }

    protected static ?string $slug = 'tambah-dokumen-baru';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.tambah-dokumen-baru';

    public ?array $data = [];

    public bool $isCreating = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        // Mahasiswa tidak memiliki akses input kerjasama
        if ($user?->isMahasiswa()) {
            return false;
        }

        return true;
    }

    public function mount(): void
    {
        $user = Auth::user();
        $isDosen = (bool) $user?->isDosen();

        $this->form->fill([
            'jenis_dokumen_id' => $isDosen ? 4 : 1,
            'jenis' => 'Dalam Negeri',
            'jenis_pengajuan' => 'Baru',
            'jurusans' => ($isDosen && $jurusanId = $user?->getJurusanId()) ? [$jurusanId] : [],
            'prodis' => ($isDosen) ? $user?->getProdiIds() : [],
        ]);
    }

    protected function getFormModel(): Model | string | null
    {
        return Kerjasama::class;
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();
        $isDosen = (bool) $user?->isDosen();

        $jenisDokumenOptions = $isDosen
            ? [
                4 => 'IA (Implementation Arrangement)',
            ]
            : [
                1 => 'MoU (Nota Kesepahaman)',
                7 => 'LoI (Letter of Intent)',
                3 => 'PKS (Perjanjian Kerja Sama)',
                2 => 'MoA (Memorandum of Agreement)',
                5 => 'SPK (Surat Perjanjian Kerja)',
                6 => 'LoC (Letter of Collaboration)',
                4 => 'IA (Implementation Arrangement)',
            ];

        return $schema
            ->model(Kerjasama::class)
            ->schema([
                Section::make('Berkas Dokumen')
                    ->description('Unggah berkas kerja sama dalam format PDF untuk membaca dan mengisi data secara otomatis.')
                    ->schema([
                        Forms\Components\FileUpload::make('link_dokumen')
                            ->label('Berkas Dokumen Kerja Sama (.pdf)')
                            ->required()
                            ->hintAction(GeminiOcrService::getAutoFillAction())
                            ->disk('google')
                            ->directory(function (callable $get) {
                                $jenisDokumenId = (int) ($get('jenis_dokumen_id') ?? 1);
                                $isLn = ($get('jenis') === 'Luar Negeri');

                                $folder = match ($jenisDokumenId) {
                                    1 => $isLn ? 'MoU LN' : 'MoU',
                                    7 => $isLn ? 'LoI LN' : 'LoI',
                                    2 => $isLn ? 'MoA LN' : 'MoA',
                                    3 => $isLn ? 'PKS LN' : 'PKS',
                                    4 => $isLn ? 'IA LN' : 'IA',
                                    5 => 'SPK',
                                    6 => 'LoC',
                                    default => 'Dokumen',
                                };

                                return $folder . '/' . date('Y/m/d');
                            })
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(25600)
                            ->helperText('Format berkas PDF, ukuran maksimal 25 MB. Klik "Isi Otomatis" di kanan label untuk membaca data.')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                $jenisDokumenId = (int) ($get('jenis_dokumen_id') ?? 1);
                                $isLn = ($get('jenis') === 'Luar Negeri');

                                $folder = match ($jenisDokumenId) {
                                    1 => $isLn ? 'MoU LN' : 'MoU',
                                    7 => $isLn ? 'LoI LN' : 'LoI',
                                    2 => $isLn ? 'MoA LN' : 'MoA',
                                    3 => $isLn ? 'PKS LN' : 'PKS',
                                    4 => $isLn ? 'IA LN' : 'IA',
                                    5 => 'SPK',
                                    6 => 'LoC',
                                    default => 'Dokumen',
                                };

                                $dir = $folder . '/' . date('Y/m/d');
                                try {
                                    $existingFiles = Storage::disk('google')->files($dir);
                                    $count = count($existingFiles) + 1;
                                } catch (\Exception $e) {
                                    $count = 1;
                                }
                                $sequence = sprintf('%03d', $count);
                                $type = $folder;
                                $originalName = str_replace('&', '_', $file->getClientOriginalName());
                                return "{$sequence}_{$type}_{$originalName}";
                            })
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('ai_detected_category')
                            ->dehydrated()
                            ->live(),

                        Forms\Components\Hidden::make('ai_detected_reason')
                            ->dehydrated()
                            ->live(),

                        Forms\Components\Placeholder::make('ai_dosen_category_warning')
                            ->hiddenLabel()
                            ->visible(function (Get $get) use ($isDosen) {
                                if (!$isDosen) {
                                    return false;
                                }
                                $cat = strtoupper(trim((string) ($get('ai_detected_category') ?? '')));
                                return !empty($cat) && !in_array($cat, ['IA', 'IMPLEMENTATION ARRANGEMENT']);
                            })
                            ->content(function (Get $get) {
                                $cat = htmlspecialchars(trim((string) ($get('ai_detected_category') ?? 'Bukan IA')));
                                $reason = htmlspecialchars(trim((string) ($get('ai_detected_reason') ?? '')));
                                $reasonHtml = $reason ? "<div style=\"margin-top: 6px; font-size: 0.825rem; color: #b91c1c;\"><strong>Alasan deteksi:</strong> {$reason}</div>" : "";

                                return new \Illuminate\Support\HtmlString("
                                    <div style=\"background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 14px 16px; border-radius: 8px; font-size: 0.875rem;\">
                                        <div style=\"font-weight: 700; display: flex; align-items: center; gap: 8px; font-size: 0.95rem;\">
                                            <svg style=\"width: 20px; height: 20px; flex-shrink: 0; fill: currentColor;\" viewBox=\"0 0 20 20\">
                                                <path fill-rule=\"evenodd\" d=\"M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z\" clip-rule=\"evenodd\"/>
                                            </svg>
                                            Peringatan: Berkas Terdeteksi Sebagai Dokumen {$cat}
                                        </div>
                                        <div style=\"margin-top: 6px; color: #7f1d1d; line-height: 1.45;\">
                                            Role Dosen hanya memiliki wewenang untuk menambahkan dokumen <strong>Implementation Arrangement (IA)</strong>. Dokumen berjenis {$cat} tidak dapat disimpan. Silakan unggah berkas IA yang sesuai atau hubungi Tim Kerja Sama Polinema jika memerlukan pemrosesan dokumen MoU/PKS.
                                        </div>
                                        {$reasonHtml}
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('jenis_dokumen_id')
                            ->label('Jenis Dokumen')
                            ->options($jenisDokumenOptions)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText($isDosen ? 'Dosen hanya memiliki hak akses untuk menambahkan dokumen IA (Implementation Arrangement).' : 'Pilihan ini terisi otomatis dari berkas dan dapat Anda sesuaikan jika diperlukan.')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (in_array((int) $state, [2, 6])) {
                                    $set('jenis', 'Luar Negeri');
                                } elseif (in_array((int) $state, [1, 3, 5])) {
                                    $set('jenis', 'Dalam Negeri');
                                }
                            }),

                        Forms\Components\TextInput::make('judul')
                            ->label('Nama / Judul Dokumen')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Nota Kesepahaman antara Polinema dengan PT Telkom Indonesia'),
                    ])
                    ->columns(2),

                Section::make('Informasi Mitra & Lokasi')
                    ->schema([
                        Forms\Components\Select::make('mitra_id')
                            ->label('Nama Mitra')
                            ->relationship('mitra', 'nama_mitra')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->createOptionForm(static::getMitraCreateFormSchema())
                            ->createOptionUsing(function (array $data) {
                                return Mitra::query()->create($data)->getKey();
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (! $state) {
                                    return;
                                }

                                $docId = (int) ($get('jenis_dokumen_id') ?? 1);

                                // Cari MoU induk jika dokumen yang diinput adalah PKS atau IA
                                if (in_array($docId, [2, 3, 4, 5, 6])) {
                                    $latestMou = Kerjasama::query()
                                        ->where('mitra_id', $state)
                                        ->whereIn('jenis_dokumen_id', [1, 7])
                                        ->latest('tanggal_awal')
                                        ->first();

                                    $set('parent_id', $latestMou?->id);
                                }

                                // Cari PKS induk jika dokumen adalah IA
                                if ($docId === 4) {
                                    $latestPks = Kerjasama::query()
                                        ->where('mitra_id', $state)
                                        ->whereIn('jenis_dokumen_id', [2, 3, 5, 6])
                                        ->latest('tanggal_awal')
                                        ->first();

                                    $set('pks_id', $latestPks?->id);
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make('jenis')
                            ->label('Cakupan Kerjasama (DN / LN)')
                            ->options([
                                'Dalam Negeri' => 'Dalam Negeri',
                                'Luar Negeri' => 'Luar Negeri',
                            ])
                            ->required()
                            ->default('Dalam Negeri'),

                        ...static::getKerjasamaLocationFormSchema(),

                        Forms\Components\Select::make('bidang_id')
                            ->label('Bidang Kerjasama')
                            ->relationship('bidang', 'bidang_kerjasama')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Dokumen Induk & Unit Pelaksana')
                    ->description('Pilih dokumen payung (MoU / PKS) serta Jurusan dan Program Studi yang menjalankan kerja sama ini.')
                    ->visible(fn (Get $get): bool => in_array((int) $get('jenis_dokumen_id'), [2, 3, 4, 5, 6]))
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Referensi Dokumen MoU Induk')
                            ->options(function (Get $get) {
                                $mitraId = $get('mitra_id');
                                if (! $mitraId) {
                                    return [];
                                }

                                return Kerjasama::query()
                                    ->where('mitra_id', $mitraId)
                                    ->whereIn('jenis_dokumen_id', [1, 7])
                                    ->whereNotNull('judul')
                                    ->where('judul', '!=', '')
                                    ->pluck('judul', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Pilih dokumen MoU yang memayungi PKS atau IA ini.'),

                        Forms\Components\Select::make('pks_id')
                            ->label('Referensi Dokumen PKS / MoA (Khusus IA)')
                            ->visible(fn (Get $get): bool => (int) $get('jenis_dokumen_id') === 4)
                            ->options(function (Get $get) {
                                $mitraId = $get('mitra_id');
                                if (! $mitraId) {
                                    return [];
                                }

                                return Kerjasama::query()
                                    ->where('mitra_id', $mitraId)
                                    ->whereIn('jenis_dokumen_id', [2, 3, 5, 6])
                                    ->whereNotNull('judul')
                                    ->where('judul', '!=', '')
                                    ->pluck('judul', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Pilih dokumen PKS yang menjadi dasar pelaksanaan IA ini (jika ada).'),

                        Forms\Components\Select::make('jurusans')
                            ->label('Jurusan')
                            ->relationship('jurusans', 'nama_jurusan')
                            ->default(function () {
                                $user = Auth::user();
                                if ($user?->isDosen() && $jurusanId = $user->getJurusanId()) {
                                    return [$jurusanId];
                                }
                                return [];
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('prodis', [])),

                        Forms\Components\Select::make('prodis')
                            ->label('Program Studi')
                            ->relationship('prodis', 'nama_prodi', function ($query, callable $get) {
                                $jurusans = $get('jurusans');
                                if (!empty($jurusans)) {
                                    $query->whereIn('jurusan_id', $jurusans);
                                }
                            })
                            ->default(function () {
                                $user = Auth::user();
                                if ($user?->isDosen()) {
                                    return $user->getProdiIds();
                                }
                                return [];
                            })
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Nomor Dokumen & Masa Berlaku')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_dokumen_polinema')
                            ->label('Nomor Dokumen Polinema')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Jika nomor dokumen ditulis tangan bisa dicek terlebih dahulu.'),

                        Forms\Components\TextInput::make('nomor_dokumen_mitra')
                            ->label('Nomor Dokumen Mitra')
                            ->maxLength(100),

                        Forms\Components\DatePicker::make('tanggal_awal')
                            ->label('Tanggal Berlaku / Penandatanganan')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Berakhir')
                            ->required()
                            ->helperText(fn (Get $get) => (int) $get('jenis_dokumen_id') === 4
                                ? 'Untuk IA, jika berkas tidak menyebutkan tanggal berakhir, otomatis berlaku 1 tahun dari tanggal berlaku.'
                                : 'Tanggal berakhirnya masa berlaku dokumen kerjasama.'),

                        Forms\Components\Select::make('jenis_pengajuan')
                            ->label('Jenis Pengajuan')
                            ->options([
                                'Baru' => 'Dokumen Baru',
                                'Perpanjangan' => 'Perpanjangan (Extension)',
                            ])
                            ->default('Baru')
                            ->required(),

                        Forms\Components\TextInput::make('link_perbaikan')
                            ->label('Link Perbaikan (Opsional)')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('bukti_kegiatan')
                            ->label('Link Bukti Kegiatan (Opsional)')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $this->saveDocument(another: false);
    }

    public function createAnother(): void
    {
        $this->saveDocument(another: true);
    }

    protected function saveDocument(bool $another = false): void
    {
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        try {
            $data = $this->form->getState();
            $user = Auth::user();

        // Validasi batasan Dosen: hanya boleh mengunggah IA (4)
        if ($user?->isDosen()) {
            if ((int) ($data['jenis_dokumen_id'] ?? 1) !== 4) {
                $this->isCreating = false;
                Notification::make()
                    ->title('Akses Dibatasi')
                    ->body('Dosen hanya dapat menambahkan dokumen Implementation Arrangement (IA).')
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }

            // 1. Validasi berdasarkan hasil AI OCR
            $detectedCat = strtoupper(trim((string) ($data['ai_detected_category'] ?? $this->data['ai_detected_category'] ?? '')));
            if ($detectedCat && !in_array($detectedCat, ['IA', 'IMPLEMENTATION ARRANGEMENT'])) {
                Notification::make()
                    ->title('Dokumen Tidak Dapat Disimpan')
                    ->body("Berkas yang Anda unggah terdeteksi sebagai dokumen {$detectedCat}. Dosen hanya diperkenankan mengunggah dokumen Implementation Arrangement (IA). Silakan periksa kembali berkas yang Anda pilih.")
                    ->danger()
                    ->persistent()
                    ->send();
                $this->isCreating = false;
                return;
            }

            // 2. Validasi tambahan jika belum melalui tombol Isi Otomatis tapi nama berkas jelas MoU/PKS
            $fileRaw = $data['link_dokumen'] ?? null;
            $fileName = is_array($fileRaw) ? (array_values($fileRaw)[0] ?? '') : $fileRaw;
            if (is_string($fileName) && !empty($fileName)) {
                $lowerName = strtolower(basename($fileName));
                if (preg_match('/\b(mou|nota[-_ ]kesepahaman)\b/i', $lowerName)) {
                    Notification::make()
                        ->title('Dokumen Tidak Dapat Disimpan')
                        ->body('Berkas yang Anda unggah terindikasi sebagai dokumen MoU. Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).')
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                } elseif (preg_match('/\b(pks|spk|perjanjian[-_ ]kerja[-_ ]sama|perjanjian[-_ ]kerjasama)\b/i', $lowerName)) {
                    Notification::make()
                        ->title('Dokumen Tidak Dapat Disimpan')
                        ->body('Berkas yang Anda unggah terindikasi sebagai dokumen PKS/SPK. Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).')
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }
            }
        }

        // Susun nomor_dokumen gabungan
        $pol = trim($data['nomor_dokumen_polinema'] ?? '');
        $mit = trim($data['nomor_dokumen_mitra'] ?? '');
        $nomorDokumen = null;
        if (!empty($pol) && !empty($mit)) {
            $nomorDokumen = $pol . "\n" . $mit;
        } elseif (!empty($pol)) {
            $nomorDokumen = $pol;
        } elseif (!empty($mit)) {
            $nomorDokumen = $mit;
        }

        // Tentukan tahun
        $tahun = null;
        if (!empty($data['tanggal_awal'])) {
            try {
                $tahun = \Carbon\Carbon::parse($data['tanggal_awal'])->year;
            } catch (\Throwable $e) {}
        }

        // Tentukan tanggal akhir jika IA kosong
        $tanggalAkhir = $data['tanggal_akhir'] ?? null;
        if ((int) $data['jenis_dokumen_id'] === 4 && empty($tanggalAkhir) && !empty($data['tanggal_awal'])) {
            try {
                $tanggalAkhir = \Carbon\Carbon::parse($data['tanggal_awal'])->addYear()->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        // Siapkan atribut record Kerjasama
        $recordData = [
            'jenis_dokumen_id' => $data['jenis_dokumen_id'],
            'judul' => $data['judul'],
            'link_dokumen' => $data['link_dokumen'],
            'mitra_id' => $data['mitra_id'],
            'jenis' => $data['jenis'],
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'kota_id' => $data['kota_id'] ?? null,
            'bidang_id' => $data['bidang_id'],
            'nomor_dokumen' => $nomorDokumen,
            'tanggal_awal' => $data['tanggal_awal'],
            'tanggal_akhir' => $tanggalAkhir,
            'tahun' => $tahun,
            'jenis_pengajuan' => $data['jenis_pengajuan'] ?? 'Baru',
            'link_perbaikan' => $data['link_perbaikan'] ?? null,
            'bukti_kegiatan' => $data['bukti_kegiatan'] ?? null,
            'user_id' => $user?->id,
            'status_workflow' => 'Selesai',
        ];

        // Khusus PKS & IA
        if (in_array((int) $data['jenis_dokumen_id'], [2, 3, 4, 5, 6])) {
            $recordData['parent_id'] = $data['parent_id'] ?? null;
        }

        // Khusus IA
        if ((int) $data['jenis_dokumen_id'] === 4) {
            $recordData['pks_id'] = $data['pks_id'] ?? null;
        }

        $record = Kerjasama::create($recordData);

        // Pasang relasi Jurusan & Prodi
        if (!empty($data['jurusans']) && is_array($data['jurusans'])) {
            $record->jurusans()->sync($data['jurusans']);
        }
        if (!empty($data['prodis']) && is_array($data['prodis'])) {
            $record->prodis()->sync($data['prodis']);
        }

        // Jika Dosen dan belum ada relasi terpasang, otomatis lampirkan prodi dosen
        if ($user?->isDosen()) {
            if ($record->prodis()->count() === 0 && $prodiId = $user->getProdiId()) {
                $record->prodis()->attach($prodiId);
            }
            if ($record->jurusans()->count() === 0 && $jurusanId = $user->getJurusanId()) {
                $record->jurusans()->attach($jurusanId);
            }
        }

        $jenisNama = match ((int) $record->jenis_dokumen_id) {
            1 => 'MoU',
            7 => 'LoI',
            2 => 'MoA',
            3 => 'PKS',
            4 => 'IA',
            5 => 'SPK',
            6 => 'LoC',
            default => 'Dokumen Kerjasama',
        };

        if ($another) {
            $this->isCreating = false;
            Notification::make()
                ->title("Dokumen {$jenisNama} Berhasil Disimpan!")
                ->body("'{$record->judul}' berhasil tercatat. Formulir telah dibersihkan untuk berkas berikutnya.")
                ->success()
                ->send();

            $isDosen = (bool) $user?->isDosen();
            $this->form->fill([
                'jenis_dokumen_id' => $isDosen ? 4 : 1,
                'jenis' => 'Dalam Negeri',
                'jenis_pengajuan' => 'Baru',
                'jurusans' => ($isDosen && $jurusanId = $user?->getJurusanId()) ? [$jurusanId] : [],
                'prodis' => ($isDosen) ? $user?->getProdiIds() : [],
            ]);
        } else {
            Notification::make()
                ->title("Dokumen {$jenisNama} Berhasil Disimpan!")
                ->body("'{$record->judul}' telah berhasil ditambahkan.")
                ->success()
                ->send();

            // Redirect ke halaman tabel yang sesuai
            $redirectUrl = match ((int) $record->jenis_dokumen_id) {
                1, 7 => MouResource::getUrl('index'),
                2, 3, 5, 6 => PksSpkResource::getUrl('index'),
                4 => IaResource::getUrl('index'),
                default => MouResource::getUrl('index'),
            };

            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
        }
        } catch (\Throwable $e) {
            $this->isCreating = false;
            throw $e;
        }
    }
}
