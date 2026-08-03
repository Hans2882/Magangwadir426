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

    protected static \UnitEnum|string|null $navigationGroup = 'Pelaporan & Tracking';

    protected static ?string $navigationLabel = 'Pelaporan Case Study';

    protected static ?string $modelLabel = 'Pelaporan Case Study';

    protected static ?string $pluralModelLabel = 'Pelaporan Case Study';

    protected static ?string $slug = 'pelaporan-case-study';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis_dokumen_id', 8);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas Case Study')
                ->required(fn ($get) => $get('status_workflow') === 'Selesai')
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
                    $originalName = $file->getClientOriginalName();
                    
                    return "{$sequence}_{$type}_{$originalName}";
                })
                ->columnSpanFull(),
            Forms\Components\TextInput::make('judul')->label('Judul Laporan / Kegiatan')->maxLength(255)->required(),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Toggle::make('is_mitra_anonim')
                ->label('Sembunyikan Nama Mitra untuk Publik (Anonymize)'),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(8),
            Forms\Components\Hidden::make('status_workflow')->default('Selesai'),
            Forms\Components\Hidden::make('jenis_pengajuan')->default('Baru'),
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
                ->dehydrateStateUsing(function (\Filament\Schemas\Components\Utilities\Get $get) {
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
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Pelaporan Case Study')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('jenis')
                        ->label('Cakupan (DN/LN)')
                        ->badge()
                        ->color(fn ($state) => $state === 'Dalam Negeri' ? 'success' : 'warning')
                        ->icon(fn ($state) => $state === 'Dalam Negeri' ? 'heroicon-o-building-office' : 'heroicon-o-globe-americas')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('judul')
                        ->label('Judul Laporan / Kegiatan')
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
                        ->url(fn ($record) => $record->link_dokumen ? \Illuminate\Support\Facades\Storage::disk('google')->url(is_array($record->link_dokumen) ? array_values($record->link_dokumen)[0] : $record->link_dokumen) : null, true)
                        ->color('primary')
                        ->badge()
                        ->icon('heroicon-o-document-arrow-down')
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
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->judul),
                Tables\Columns\TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->mitra?->nama_mitra),
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
                //
            ])
            ->actions([
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
