<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelaporanKegiatanResource\Pages;
use App\Models\Kerjasama;
use App\Models\MasterJurusan;
use App\Models\MasterProgramStudi;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PelaporanKegiatanResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Luaran Dokumen kerjasama';

    protected static ?string $navigationLabel = 'Pelaporan Kegiatan';

    protected static ?string $modelLabel = 'Pelaporan Kegiatan';

    protected static ?string $pluralModelLabel = 'Pelaporan Kegiatan';

    protected static ?string $slug = 'pelaporan-kegiatan';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isMahasiswa()) {
            return (int) $record->user_id === (int) $user->id;
        }

        if ($user->isKoorMagang()) {
            $prodiIds = $user->getProdiIds();
            if (!empty($prodiIds)) {
                return $record->prodis()->whereIn('master_program_studi.id', $prodiIds)->exists();
            }
            return (int) $record->user_id === (int) $user->id;
        }

        if ($user->isDosen()) {
            $jurusanId = $user->getJurusanId();
            $prodiId = $user->getProdiId();

            if ($jurusanId || $prodiId) {
                $matchesJurusan = $jurusanId && (
                    $record->jurusans()->where('master_jurusans.id', $jurusanId)->exists()
                    || $record->prodis()->where('master_program_studi.jurusan_id', $jurusanId)->exists()
                );

                $matchesProdi = $prodiId && $record->prodis()->where('master_program_studi.id', $prodiId)->exists();
                $isAuthor = (int) $record->user_id === (int) $user->id;

                return $matchesJurusan || $matchesProdi || $isAuthor;
            }

            return (int) $record->user_id === (int) $user->id;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        if (! static::canView($record)) {
            return false;
        }
        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        if (! static::canView($record)) {
            return false;
        }
        return parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        return parent::canDeleteAny();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['mitra', 'pengusul', 'parent.jenisDokumen', 'prodis', 'jurusans', 'jenisDokumen'])
            ->whereIn('jenis_dokumen_id', [8, 9]); // 8 = Case Study, 9 = Press Release

        $user = \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isKoorMagang()) {
            $prodiIds = $user->getProdiIds();
            if (!empty($prodiIds)) {
                return $query->whereHas('prodis', function ($q) use ($prodiIds) {
                    $q->whereIn('master_program_studi.id', $prodiIds);
                });
            }

            return $query->where('user_id', $user->id);
        }

        if ($user->isDosen()) {
            $jurusanId = $user->getJurusanId();
            $prodiId = $user->getProdiId();

            if ($jurusanId || $prodiId) {
                return $query->where(function (Builder $sub) use ($jurusanId, $prodiId, $user) {
                    if ($jurusanId) {
                        $sub->whereHas('jurusans', fn ($q) => $q->where('master_jurusans.id', $jurusanId))
                            ->orWhereHas('prodis', fn ($q) => $q->where('master_program_studi.jurusan_id', $jurusanId));
                    }
                    if ($prodiId) {
                        $sub->orWhereHas('prodis', fn ($q) => $q->where('master_program_studi.id', $prodiId));
                    }
                    $sub->orWhere('user_id', $user->id);
                });
            }

            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Radio::make('jenis_dokumen_id')
                ->label('Kategori Kegiatan')
                ->options([
                    8 => 'Case Study (Laporan Berkas PDF & Wajib Mitra)',
                    9 => 'Press Release (Tautan Berita & Mitra Opsional)',
                ])
                ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->isDosen() ? 9 : 8)
                ->inline()
                ->live()
                ->required()
                ->columnSpanFull(),

            // Link Press Release: visible and required only if jenis_dokumen_id == 9
            Forms\Components\TextInput::make('link_press_release')
                ->label('Link Press Release')
                ->placeholder('https://polinema.ac.id/berita/')
                ->url()
                ->hintAction(\App\Services\GeminiPressReleaseService::getAutoFillAction())
                ->required(fn ($get) => (int) $get('jenis_dokumen_id') === 9)
                ->validationMessages([
                    'required' => 'Link Press Release wajib diisi.',
                    'url' => 'Format tautan tidak valid (harus diawali http:// atau https://).',
                ])
                ->visible(fn ($get) => (int) $get('jenis_dokumen_id') === 9)
                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
                    if ($record && (int) $record->jenis_dokumen_id === 9) {
                        $component->state($record->link_dokumen);
                    }
                })
                ->dehydrated(fn ($get) => (int) $get('jenis_dokumen_id') === 9)
                ->helperText('Masukkan tautan URL publikasi berita, lalu gunakan tombol Auto-Fill via AI untuk mengisi form otomatis.')
                ->columnSpanFull(),

            // Berkas Case Study: visible and required only if jenis_dokumen_id == 8
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas Case Study')
                ->required(fn ($get) => (int) $get('jenis_dokumen_id') === 8 || ! $get('jenis_dokumen_id'))
                ->validationMessages(['required' => 'Berkas wajib diunggah untuk Case Study.'])
                ->dehydrated(fn ($get) => (int) ($get('jenis_dokumen_id') ?? 8) === 8)
                ->hintAction(\App\Services\GeminiOcrService::getAutoFillAction())
                ->disk('google')
                ->directory(function () {
                    return 'StudyCase/' . date('Y/m/d');
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(25600)
                ->helperText('Ukuran maksimal berkas: 25 MB (format PDF).')
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string {
                    $dir = 'StudyCase/' . date('Y/m/d');
                    
                    try {
                        $existingFiles = \Illuminate\Support\Facades\Storage::disk('google')->files($dir);
                        $count = count($existingFiles) + 1;
                    } catch (\Exception $e) {
                        $count = 1;
                    }
                    
                    $sequence = sprintf('%03d', $count);
                    $type = 'Case_Study';
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                    $cleanName = \Illuminate\Support\Str::slug($originalName);
                    
                    return "{$sequence}_{$type}_{$cleanName}.{$extension}";
                })
                ->visible(fn ($get) => (int) $get('jenis_dokumen_id') === 8 || ! $get('jenis_dokumen_id'))
                ->columnSpanFull(),

            Forms\Components\TextInput::make('judul')
                ->label('Judul Laporan / Kegiatan / Berita')
                ->maxLength(255)
                ->required(),

            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required(fn ($get) => (int) $get('jenis_dokumen_id') === 8)
                ->helperText(fn ($get) => (int) $get('jenis_dokumen_id') === 9 ? 'Opsional untuk Press Release.' : 'Wajib dipilih untuk Case Study.')
                ->live()
                ->afterStateUpdated(fn ($set) => $set('parent_id', null)),

            Forms\Components\Select::make('parent_id')
                ->label('Dokumen Rujukan (MoU / PKS / IA)')
                ->required(fn ($get) => (int) $get('jenis_dokumen_id') === 8)
                ->helperText(fn ($get) => (int) $get('jenis_dokumen_id') === 9 ? 'Opsional jika Press Release berkaitan dengan dokumen kerjasama.' : 'Wajib dipilih untuk Case Study.')
                ->options(function ($get) {
                    $mitraId = $get('mitra_id');
                    if (! $mitraId) {
                        return [];
                    }
                    return \App\Models\Kerjasama::query()
                        ->where('mitra_id', $mitraId)
                        ->whereIn('jenis_dokumen_id', [1, 3, 4]) // MoU, PKS, IA
                        ->with('jenisDokumen')
                        ->get()
                        ->mapWithKeys(function ($item) {
                            $tipe = $item->jenisDokumen ? $item->jenisDokumen->nama : 'Dokumen';
                            return [$item->id => "{$tipe} - {$item->judul}"];
                        });
                })
                ->searchable()
                ->preload(),

            Forms\Components\Toggle::make('is_mitra_anonim')
                ->label('Sembunyikan Nama Mitra untuk Publik (Anonymize)')
                ->visible(fn ($get) => !empty($get('mitra_id'))),

            Forms\Components\Hidden::make('status_workflow')->default(fn () => (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa() || \Illuminate\Support\Facades\Auth::user()?->isDosen()) ? 'Proses' : 'Selesai'),
            Forms\Components\Hidden::make('user_id')->default(fn () => \Illuminate\Support\Facades\Auth::id()),
            Forms\Components\Hidden::make('jenis_pengajuan')->default('Baru'),

            \Filament\Schemas\Components\Section::make('Catatan Penolakan')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('feedback_placeholder')
                        ->label('')
                        ->content(fn ($record) => $record?->feedback ?: '-'),
                ])
                ->visible(fn ($record) => $record && $record->status_workflow === 'Ditolak'),

            Forms\Components\Select::make('jenis')
                ->label('Cakupan (DN/LN)')
                ->options([
                    'Dalam Negeri' => 'Dalam Negeri',
                    'Luar Negeri' => 'Luar Negeri',
                ])
                ->required()
                ->default('Dalam Negeri'),

            Forms\Components\TextInput::make('nomor_dokumen_polinema')
                ->label('Nomor Surat Tugas')
                ->maxLength(100)
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
                    if ($record && $record->nomor_dokumen) {
                        $component->state($record->nomor_dokumen);
                    }
                }),

            Forms\Components\Hidden::make('nomor_dokumen')
                ->dehydrateStateUsing(function ($get) {
                    return trim($get('nomor_dokumen_polinema') ?? '');
                }),

            Forms\Components\DatePicker::make('tanggal_awal')
                ->label(fn ($get) => (int) $get('jenis_dokumen_id') === 9 ? 'Tanggal Terbit Berita' : 'Tanggal Pelaksanaan')
                ->required(),

            Forms\Components\Select::make('prodis')
                ->label('Bidang Studi (Prodi)')
                ->relationship('prodis', 'nama_prodi')
                ->multiple()
                ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->getProdiId() ? [\Illuminate\Support\Facades\Auth::user()->getProdiId()] : [])
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('jurusans')
                ->label('Jurusan')
                ->relationship('jurusans', 'nama_jurusan')
                ->multiple()
                ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->getJurusanId() ? [\Illuminate\Support\Facades\Auth::user()->getJurusanId()] : [])
                ->searchable()
                ->preload(),

            Forms\Components\Textarea::make('feedback')
                ->label('Feedback / Catatan Evaluasi')
                ->rows(4)
                ->columnSpanFull()
                ->hiddenOn('create'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Pelaporan Kegiatan')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('jenisDokumen.nama')
                        ->label('Kategori')
                        ->badge()
                        ->color(fn ($record) => (int) $record->jenis_dokumen_id === 9 ? 'success' : 'info'),
                    \Filament\Infolists\Components\TextEntry::make('status_workflow')
                        ->label('Status Approval')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'Proses' => 'warning',
                            'Selesai' => 'success',
                            'Ditolak' => 'danger',
                            default => 'primary',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('pengusul.name')
                        ->label('Pengusul')
                        ->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('jenis')
                        ->label('Cakupan (DN/LN)')
                        ->badge()
                        ->color(fn ($state) => $state === 'Dalam Negeri' ? 'success' : 'warning')
                        ->icon(fn ($state) => $state === 'Dalam Negeri' ? 'heroicon-o-building-office' : 'heroicon-o-globe-americas'),
                    \Filament\Infolists\Components\TextEntry::make('judul')
                        ->label('Judul Laporan / Kegiatan')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('parent.judul')
                        ->label('Dokumen Rujukan')
                        ->default('-')
                        ->formatStateUsing(function ($state, $record) {
                            if (!$record->parent) return '-';
                            $tipe = $record->parent->jenisDokumen ? $record->parent->jenisDokumen->nama : 'Dokumen';
                            return "{$tipe} - {$state}";
                        })
                        ->url(function ($record) {
                            if (!$record->parent) return null;
                            if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) return null;
                            return match ($record->parent->jenis_dokumen_id) {
                                1 => \App\Filament\Resources\MouResource::getUrl('view', ['record' => $record->parent_id]),
                                2, 3, 5, 6 => \App\Filament\Resources\PksSpkResource::getUrl('view', ['record' => $record->parent_id]),
                                4 => \App\Filament\Resources\IaResource::getUrl('view', ['record' => $record->parent_id]),
                                default => null,
                            };
                        })
                        ->openUrlInNewTab()
                        ->color('primary')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('mitra.nama_mitra')
                        ->label('Nama Mitra')
                        ->default('-')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('negara')
                        ->label('Negara')
                        ->getStateUsing(fn ($record) => $record->jenis === 'Luar Negeri' ? ($record->mitra?->negara?->nama_negara ?? '-') : 'Indonesia')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('nomor_dokumen')
                        ->label('Nomor Surat Tugas')
                        ->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_awal')
                        ->label('Tanggal Pelaksanaan / Terbit')
                        ->date('d F Y'),
                    \Filament\Infolists\Components\TextEntry::make('prodis.nama_prodi')
                        ->label('Bidang Studi (Prodi)')
                        ->badge()
                        ->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('jurusans.nama_jurusan')
                        ->label('Jurusan')
                        ->badge()
                        ->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('link_dokumen')
                        ->label('Berkas Dokumen / Tautan')
                        ->formatStateUsing(function ($state, $record, ?Model $relatedRecord = null) {
                            $rec = $record ?? $relatedRecord;
                            if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                                return (!empty($state) && $state !== '-') ? 'Buka Tautan Berita' : '-';
                            }
                            if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                                return 'Dokumen Terkunci (Hanya Staf/Admin)';
                            }
                            return (!empty($state) && $state !== '-') ? 'Lihat Dokumen' : '-';
                        })
                        ->url(function ($state, $record, ?Model $relatedRecord = null) {
                            if (!$state || $state === '-') {
                                return null;
                            }
                            $rec = $record ?? $relatedRecord;
                            $url = is_array($state) ? array_values($state)[0] : $state;
                            $url = trim((string) $url);

                            if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9 || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                                if (!empty($url) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                                    $url = 'https://' . $url;
                                }
                                return !empty($url) ? $url : null;
                            }
                            if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                                return null;
                            }
                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                            $disk = \Illuminate\Support\Facades\Storage::disk('google');
                            return $disk->url(is_array($state) ? array_values($state)[0] : $state);
                        }, true)
                        ->color(function ($state, $record, ?Model $relatedRecord = null) {
                            if (empty($state) || $state === '-') {
                                return 'gray';
                            }
                            $rec = $record ?? $relatedRecord;
                            if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                                return 'success';
                            }
                            return \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'gray' : 'primary';
                        })
                        ->badge(fn ($state) => !empty($state) && $state !== '-')
                        ->icon(function ($state, $record, ?Model $relatedRecord = null) {
                            if (empty($state) || $state === '-') {
                                return null;
                            }
                            $rec = $record ?? $relatedRecord;
                            if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                                return 'heroicon-o-arrow-top-right-on-square';
                            }
                            return \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'heroicon-o-lock-closed' : 'heroicon-o-document-arrow-down';
                        })
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('feedback')
                        ->label('Alasan Penolakan / Catatan Evaluasi')
                        ->default('-')
                        ->color(fn ($record) => $record->status_workflow === 'Ditolak' ? 'danger' : 'gray')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenisDokumen.nama')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($record) => (int) $record->jenis_dokumen_id === 9 ? 'success' : 'info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->judul),
                Tables\Columns\TextColumn::make('parent.jenisDokumen.nama')
                    ->label('Rujukan Kerjasama')
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin() || \Illuminate\Support\Facades\Auth::user()?->isKoorMagang())
                    ->badge()
                    ->color(fn ($state, $record) => match ($record->parent?->jenis_dokumen_id) {
                        1 => 'primary',       // MoU
                        2 => 'info',          // MoA
                        3, 5, 6 => 'warning', // PKS, SPK, LoC
                        4 => 'success',       // IA
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->parent) {
                            return 'Tidak Terhubung';
                        }
                        return $record->parent->jenisDokumen?->nama ?? 'Kerjasama';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->parent) {
                            return 'Tidak terhubung ke dokumen MoU/PKS/IA';
                        }
                        $tipe = $record->parent->jenisDokumen?->nama ?? 'Dokumen';
                        $nomor = $record->parent->nomor_dokumen ?: '-';
                        $judul = $record->parent->judul ?: '-';
                        return "{$tipe} | No: {$nomor}\nJudul: {$judul}";
                    })
                    ->url(function ($record) {
                        if (!$record->parent) return null;
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if (!$user || (!$user->isAdmin() && !$user->isKoorMagang())) return null;

                        return match ($record->parent->jenis_dokumen_id) {
                            1 => \App\Filament\Resources\MouResource::getUrl('view', ['record' => $record->parent_id]),
                            2, 3, 5, 6 => \App\Filament\Resources\PksSpkResource::getUrl('view', ['record' => $record->parent_id]),
                            4 => \App\Filament\Resources\IaResource::getUrl('view', ['record' => $record->parent_id]),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->limit(25)
                    ->default('-')
                    ->tooltip(fn ($record) => $record->mitra?->nama_mitra ?? '-'),
                Tables\Columns\TextColumn::make('pengusul.name')
                    ->label('Pengusul')
                    ->searchable()
                    ->default('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status_workflow')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Proses' => 'warning',
                        'Selesai' => 'success',
                        'Ditolak' => 'danger',
                        default => 'primary',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('negara')
                    ->label('Negara')
                    ->getStateUsing(function ($record) {
                        return $record->jenis === 'Luar Negeri'
                            ? ($record->mitra?->negara?->nama_negara ?? '-')
                            : 'Indonesia';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('link_dokumen')
                    ->label('Dokumen / Tautan')
                    ->formatStateUsing(function ($state, $record, ?Model $relatedRecord = null) {
                        $rec = $record ?? $relatedRecord;
                        if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                            return (!empty($state) && $state !== '-') ? 'Buka Tautan' : '-';
                        }
                        if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                            return 'Terkunci';
                        }
                        return (!empty($state) && $state !== '-') ? 'Lihat Berkas' : '-';
                    })
                    ->url(function ($state, $record, ?Model $relatedRecord = null) {
                        if (!$state || $state === '-') {
                            return null;
                        }
                        $rec = $record ?? $relatedRecord;
                        $url = is_array($state) ? array_values($state)[0] : $state;
                        $url = trim((string) $url);

                        if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9 || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                            if (!empty($url) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                                $url = 'https://' . $url;
                            }
                            return !empty($url) ? $url : null;
                        }
                        if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                            return null;
                        }
                        return route('view-dokumen', ['path' => is_array($state) ? array_values($state)[0] : $state]);
                    })
                    ->openUrlInNewTab()
                    ->badge(fn ($state) => !empty($state) && $state !== '-')
                    ->icon(function ($state, $record, ?Model $relatedRecord = null) {
                        if (empty($state) || $state === '-') {
                            return null;
                        }
                        $rec = $record ?? $relatedRecord;
                        if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                            return 'heroicon-o-arrow-top-right-on-square';
                        }
                        return \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'heroicon-o-lock-closed' : 'heroicon-o-eye';
                    })
                    ->color(function ($state, $record, ?Model $relatedRecord = null) {
                        if (empty($state) || $state === '-') {
                            return 'gray';
                        }
                        $rec = $record ?? $relatedRecord;
                        if ((int) ($rec?->jenis_dokumen_id ?? 0) === 9) {
                            return 'success';
                        }
                        return \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'gray' : 'primary';
                    })
                    ->extraAttributes(function ($state, $record, ?Model $relatedRecord = null) {
                        $rec = $record ?? $relatedRecord;
                        if ((int) ($rec?->jenis_dokumen_id ?? 0) !== 9 && \Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                            return [];
                        }
                        return $state && $state !== '-' ? [
                            'style' => 'cursor: pointer; transition: opacity 0.2s;',
                            'onmouseover' => "this.style.opacity='0.6'",
                            'onmouseout' => "this.style.opacity='1'",
                        ] : [];
                    })
                    ->default('-'),
                Tables\Columns\TextColumn::make('jurusan_prodi')
                    ->label('Jurusan / Prodi')
                    ->getStateUsing(function ($record) {
                        $jurusans = $record->jurusans->pluck('nama_jurusan')->toArray();
                        $prodis = $record->prodis->pluck('nama_prodi')->toArray();
                        $all = array_merge($jurusans, $prodis);
                        return empty($all) ? null : $all;
                    })
                    ->badge()
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_dokumen_id')
                    ->label('Kategori Kegiatan')
                    ->options([
                        8 => 'Case Study',
                        9 => 'Press Release',
                    ]),
                Tables\Filters\SelectFilter::make('keterhubungan_kerjasama')
                    ->label('Keterhubungan Dokumen')
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin() || \Illuminate\Support\Facades\Auth::user()?->isKoorMagang())
                    ->options([
                        'terhubung' => 'Semua yang Terhubung',
                        'mou' => 'Terhubung MoU',
                        'pks' => 'Terhubung PKS / SPK',
                        'ia' => 'Terhubung IA',
                        'tidak_terhubung' => 'Tidak Terhubung (Mandiri)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val === 'terhubung') {
                            $query->whereNotNull('parent_id');
                        } elseif ($val === 'tidak_terhubung') {
                            $query->whereNull('parent_id');
                        } elseif ($val === 'mou') {
                            $query->whereHas('parent', fn ($q) => $q->where('jenis_dokumen_id', 1));
                        } elseif ($val === 'pks') {
                            $query->whereHas('parent', fn ($q) => $q->whereIn('jenis_dokumen_id', [3, 5, 6]));
                        } elseif ($val === 'ia') {
                            $query->whereHas('parent', fn ($q) => $q->where('jenis_dokumen_id', 4));
                        }
                    }),
                Tables\Filters\SelectFilter::make('status_workflow')
                    ->label('Status')
                    ->options([
                        'Proses' => 'Proses',
                        'Selesai' => 'Selesai',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\Filter::make('jurusan_prodi')
                    ->form([
                        Forms\Components\Select::make('jurusans')
                            ->label('Jurusan')
                            ->options(MasterJurusan::pluck('nama_jurusan', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('prodis')
                            ->label('Program Studi')
                            ->options(MasterProgramStudi::pluck('nama_prodi', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jurusans'] ?? null,
                                fn (Builder $query, $jurusans) => $query->where(function ($sub) use ($jurusans) {
                                    $sub->whereHas('jurusans', fn ($q) => $q->whereIn('master_jurusans.id', (array) $jurusans))
                                        ->orWhereHas('prodis', fn ($q) => $q->whereIn('master_program_studi.jurusan_id', (array) $jurusans));
                                })
                            )
                            ->when(
                                $data['prodis'] ?? null,
                                fn (Builder $query, $prodis) => $query->whereHas(
                                    'prodis',
                                    fn ($q) => $q->whereIn('master_program_studi.id', (array) $prodis)
                                )
                            );
                    }),
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun Pelaksanaan')
                    ->options(function () {
                        return Kerjasama::query()
                            ->whereIn('jenis_dokumen_id', [8, 9])
                            ->whereNotNull('tahun')
                            ->distinct()
                            ->orderByDesc('tahun')
                            ->pluck('tahun', 'tahun')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('tahun', $data['value'])
                                  ->orWhereYear('tanggal_awal', $data['value']);
                            });
                        }
                    }),
                Tables\Filters\Filter::make('tanggal_pelaksanaan')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn ($query, $date) => $query->whereDate('tanggal_awal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn ($query, $date) => $query->whereDate('tanggal_awal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['dari_tanggal'])) {
                            $indicators['dari_tanggal'] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->translatedFormat('d M Y');
                        }
                        if (!empty($data['sampai_tanggal'])) {
                            $indicators['sampai_tanggal'] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->translatedFormat('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pelaporan Kegiatan')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pelaporan kegiatan ini?')
                    ->action(function ($record) {
                        $record->update(['status_workflow' => 'Selesai']);
                        \Filament\Notifications\Notification::make()->title('Pelaporan Kegiatan Disetujui')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_workflow === 'Proses' && (\Illuminate\Support\Facades\Auth::user()?->isAdmin() || \Illuminate\Support\Facades\Auth::user()?->isKoorMagang())),

                \Filament\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pelaporan Kegiatan')
                    ->form([
                        Forms\Components\Textarea::make('feedback')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_workflow' => 'Ditolak',
                            'feedback' => $data['feedback'],
                        ]);
                        \Filament\Notifications\Notification::make()->title('Pelaporan Kegiatan Ditolak')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_workflow === 'Proses' && (\Illuminate\Support\Facades\Auth::user()?->isAdmin() || \Illuminate\Support\Facades\Auth::user()?->isKoorMagang())),

                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelaporanKegiatans::route('/'),
            'create' => Pages\CreatePelaporanKegiatan::route('/create'),
            'view' => Pages\ViewPelaporanKegiatan::route('/{record}'),
            'edit' => Pages\EditPelaporanKegiatan::route('/{record}/edit'),
        ];
    }
}

