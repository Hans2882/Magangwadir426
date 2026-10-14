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
        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
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
        return parent::getEloquentQuery()
            ->with(['mitra', 'pengusul'])
            ->where('jenis_dokumen_id', 8);
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
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('jurusans')
                ->label('Jurusan')
                ->relationship('jurusans', 'nama_jurusan')
                ->multiple()
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
                            $panel = filament()->getCurrentPanel()->getId();
                            return match ($record->parent->jenis_dokumen_id) {
                                1 => route("filament.{$panel}.resources.data-mou.view", ['record' => $record->parent_id]),
                                3 => route("filament.{$panel}.resources.data-pks-spk.view", ['record' => $record->parent_id]),
                                4 => route("filament.{$panel}.resources.data-ia.view", ['record' => $record->parent_id]),
                                default => null,
                            };
                        })
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
                        ->formatStateUsing(fn ($state) => $state ? 'Lihat Dokumen' : '-')
                        ->url(function ($record) {
                            if (!$record->link_dokumen) return null;
                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                            $disk = \Illuminate\Support\Facades\Storage::disk('google');
                            return $disk->url(is_array($record->link_dokumen) ? array_values($record->link_dokumen)[0] : $record->link_dokumen);
                        }, true)
                        ->color('primary')
                        ->badge()
                        ->icon('heroicon-o-document-arrow-down')
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
                    ->formatStateUsing(fn ($state) => $state && $state !== '-' ? 'Lihat' : '-')
                    ->url(fn($state) => $state && $state !== '-' ? route('view-dokumen', ['path' => is_array($state) ? array_values($state)[0] : $state]) : null)
                    ->openUrlInNewTab()
                    ->badge()
                    ->icon(fn ($state) => $state && $state !== '-' ? 'heroicon-o-eye' : null)
                    ->color(fn ($state) => $state && $state !== '-' ? 'primary' : 'gray')
                    ->extraAttributes(fn ($state) => $state && $state !== '-' ? [
                        'style' => 'cursor: pointer; transition: opacity 0.2s;',
                        'onmouseover' => "this.style.opacity='0.6'",
                        'onmouseout' => "this.style.opacity='1'",
                    ] : [])
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
                \Filament\Tables\Filters\SelectFilter::make('status_workflow')
                    ->label('Status')
                    ->options([
                        'Proses' => 'Proses',
                        'Selesai' => 'Selesai',
                        'Ditolak' => 'Ditolak',
                    ]),
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

                \Filament\Actions\ViewAction::make()->slideOver(),
                \Filament\Actions\EditAction::make()->slideOver(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelaporanCaseStudies::route('/'),
            'create' => Pages\CreatePelaporanCaseStudy::route('/create'),
        ];
    }
}
