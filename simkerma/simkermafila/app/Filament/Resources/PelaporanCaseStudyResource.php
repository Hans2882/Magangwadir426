<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelaporanCaseStudyResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PelaporanCaseStudyResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Luaran Dokumen kerjasama';

    protected static ?string $navigationLabel = 'Pelaporan Case Study';

    protected static ?string $modelLabel = 'Pelaporan Case Study';

    protected static ?string $pluralModelLabel = 'Pelaporan Case Study';

    protected static ?string $slug = 'pelaporan-case-study';

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
            $prodiId = $user->getProdiId();
            if ($prodiId) {
                return $record->prodis()->where('master_program_studi.id', $prodiId)->exists();
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
            ->with(['mitra', 'pengusul', 'parent.jenisDokumen', 'prodis', 'jurusans'])
            ->where('jenis_dokumen_id', 8);

        $user = \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isKoorMagang()) {
            $prodiId = $user->getProdiId();
            if ($prodiId) {
                return $query->whereHas('prodis', function ($q) use ($prodiId) {
                    $q->where('master_program_studi.id', $prodiId);
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
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas Case Study')
                ->required()
                ->validationMessages(['required' => 'Berkas wajib diunggah.'])
                ->hintAction(\App\Services\GeminiOcrService::getAutoFillAction())
                ->disk('google')
                ->directory(function () {
                    return 'StudyCase/' . date('Y/m/d');
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
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
                ->columnSpanFull(),
            Forms\Components\TextInput::make('judul')->label('Judul Laporan / Kegiatan')->maxLength(255)->required(),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn ($set) => $set('parent_id', null)),
            Forms\Components\Select::make('parent_id')
                ->label('Dokumen Rujukan (MoU / PKS / IA)')
                ->required()
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
                ->label('Sembunyikan Nama Mitra untuk Publik (Anonymize)'),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(8),
            Forms\Components\Hidden::make('status_workflow')->default(fn () => \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'Proses' : 'Selesai'),
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
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Pelaksanaan')->required(),
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
            \Filament\Schemas\Components\Section::make('Detail Pelaporan Case Study')->columnSpan('full')
                ->schema([
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
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('negara')
                        ->label('Negara')
                        ->getStateUsing(fn ($record) => $record->jenis === 'Luar Negeri' ? ($record->mitra?->negara?->nama_negara ?? '-') : 'Indonesia')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('nomor_dokumen')
                        ->label('Nomor Surat Tugas')
                        ->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_awal')
                        ->label('Tanggal Pelaksanaan')
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
                        ->label('Berkas Dokumen')
                        ->formatStateUsing(function ($state) {
                            if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                                return 'Dokumen Terkunci (Hanya Staf/Admin)';
                            }
                            return $state ? 'Lihat Dokumen' : '-';
                        })
                        ->url(function ($record) {
                            if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                                return null;
                            }
                            if (!$record->link_dokumen) return null;
                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                            $disk = \Illuminate\Support\Facades\Storage::disk('google');
                            return $disk->url(is_array($record->link_dokumen) ? array_values($record->link_dokumen)[0] : $record->link_dokumen);
                        }, true)
                        ->color(fn () => \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'gray' : 'primary')
                        ->badge()
                        ->icon(fn () => \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'heroicon-o-lock-closed' : 'heroicon-o-document-arrow-down')
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
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->judul),
                Tables\Columns\TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->mitra?->nama_mitra),
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
                    ->label('Dokumen')
                    ->formatStateUsing(function ($state) {
                        if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                            return 'Terkunci';
                        }
                        return $state && $state !== '-' ? 'Lihat' : '-';
                    })
                    ->url(function ($state) {
                        if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
                            return null;
                        }
                        return $state && $state !== '-' ? route('view-dokumen', ['path' => is_array($state) ? array_values($state)[0] : $state]) : null;
                    })
                    ->openUrlInNewTab()
                    ->badge()
                    ->icon(fn () => \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'heroicon-o-lock-closed' : 'heroicon-o-eye')
                    ->color(fn () => \Illuminate\Support\Facades\Auth::user()?->isMahasiswa() ? 'gray' : 'primary')
                    ->extraAttributes(function ($state) {
                        if (\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()) {
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
                Tables\Filters\SelectFilter::make('status_workflow')
                    ->label('Status')
                    ->options([
                        'Proses' => 'Proses',
                        'Selesai' => 'Selesai',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun Pelaksanaan')
                    ->options(function () {
                        return Kerjasama::query()
                            ->where('jenis_dokumen_id', 8)
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
                            ->label('Dari Tanggal Pelaksanaan'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal Pelaksanaan'),
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
                    ->modalHeading('Setujui Pelaporan Case Study')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui laporan case study ini?')
                    ->action(function ($record) {
                        $record->update(['status_workflow' => 'Selesai']);
                        \Filament\Notifications\Notification::make()->title('Pelaporan Case Study Disetujui')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_workflow === 'Proses' && !\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()),

                \Filament\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pelaporan Case Study')
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
                        \Filament\Notifications\Notification::make()->title('Pelaporan Case Study Ditolak')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_workflow === 'Proses' && !\Illuminate\Support\Facades\Auth::user()?->isMahasiswa()),

                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelaporanCaseStudies::route('/'),
            'create' => Pages\CreatePelaporanCaseStudy::route('/create'),
            'view' => Pages\ViewPelaporanCaseStudy::route('/{record}'),
            'edit' => Pages\EditPelaporanCaseStudy::route('/{record}/edit'),
        ];
    }
}
