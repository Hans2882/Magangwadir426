<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MoaResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MoaResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data MoA';

    protected static ?string $modelLabel = 'Data MoA';

    protected static ?string $pluralModelLabel = 'Data MoA';

    protected static ?string $slug = 'data-moa';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis', 'Luar Negeri')
            ->where('jenis_dokumen_id', 2);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas MoA')
                ->required(fn ($get) => $get('status_workflow') === 'Selesai')
                ->hintAction(\App\Services\GeminiOcrService::getAutoFillAction())
                ->disk('google')
                ->directory(function () {
                    return 'MoA/' . date('Y/m/d');
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string {
                    $dir = 'MoA/' . date('Y/m/d');
                    
                    try {
                        $existingFiles = \Illuminate\Support\Facades\Storage::disk('google')->files($dir);
                        $count = count($existingFiles) + 1;
                    } catch (\Exception $e) {
                        $count = 1;
                    }
                    
                    $sequence = sprintf('%03d', $count);
                    $type = 'MoA';
                    $originalName = $file->getClientOriginalName();
                    
                    return "{$sequence}_{$type}_{$originalName}";
                })
                ->columnSpanFull(),
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra', fn (Builder $query) => $query->where('negara_id', '>=', 1))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Hidden::make('jenis')->default('Luar Negeri'),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(2),
            Forms\Components\Select::make('status_workflow')
                ->label('Status Proses')
                ->options([
                    'Draft' => 'Draft (Sedang Disusun)',
                    'Review Internal' => 'Review Internal',
                    'Menunggu TTD Mitra' => 'Menunggu TTD Mitra',
                    'Menunggu TTD Direktur' => 'Menunggu TTD Direktur',
                    'Selesai' => 'Selesai (Aktif)',
                ])
                ->default('Draft')
                ->live()
                ->required(),
            Forms\Components\Select::make('jenis_pengajuan')
                ->label('Jenis Pengajuan')
                ->options([
                    'Baru' => 'Dokumen Baru',
                    'Perpanjangan' => 'Perpanjangan (Extension)',
                ])
                ->default('Baru')
                ->required(),
            Forms\Components\TextInput::make('nomor_dokumen_polinema')
    ->label('Nomor MoA Polinema')
    ->required()
    ->maxLength(100)
    ->dehydrated(false)
    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
        if ($record && $record->nomor_dokumen) {
            $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

            if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                $parts = explode(' ', $record->nomor_dokumen, 2);
            }

            $component->state(trim($parts[0] ?? ''));
        }
    })
    ->rule(function (?Model $record) {
        return function ($attribute, $value, $fail) use ($record) {

            $query = \App\Models\Kerjasama::query()->where(
                'nomor_dokumen',
                'like',
                $value . "\n%",
                'and'
            );

            if ($record) {
                $query->where('id', '!=', $record->id);
            }

            if ($query->exists()) {
                $fail('Nomor MoA Polinema sudah digunakan.');
            }
        };
    }),

Forms\Components\TextInput::make('nomor_dokumen_mitra')
    ->label('Nomor MoA Mitra')
    ->maxLength(100)
    ->dehydrated(false)
    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
        if ($record && $record->nomor_dokumen) {
            $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

            if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                $parts = explode(' ', $record->nomor_dokumen, 2);
            }

            $component->state(trim($parts[1] ?? ''));
        }
    }),

Forms\Components\Hidden::make('nomor_dokumen')
    ->dehydrateStateUsing(function (\Filament\Schemas\Components\Utilities\Get $get) {

        $pol = trim($get('nomor_dokumen_polinema') ?? '');
        $mit = trim($get('nomor_dokumen_mitra') ?? '');

        if (empty($pol) && empty($mit)) {
            return null;
        }

        if (empty($pol)) {
            return $mit;
        }

        if (empty($mit)) {
            return $pol;
        }

        return $pol . "\n" . $mit;
    }),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Berlaku')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Akhir')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\TextInput::make('link_perbaikan')->label('Link Perbaikan')->url()->maxLength(500),
            Forms\Components\TextInput::make('bukti_kegiatan')->label('Bukti Kegiatan')->url()->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status_workflow', 'Selesai'))
            ->recordAction(null)
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->columns([
            Tables\Columns\TextColumn::make('judul')
                ->label('Judul')
                ->searchable()
                ->sortable()
                ->limit(50)
                ->tooltip(fn ($record) => $record->judul),

            Tables\Columns\TextColumn::make('mitra.nama_mitra')
                ->label('Nama Mitra')
                ->searchable()
                ->sortable()
                ->limit(50)
                ->tooltip(fn ($record) => $record->mitra?->nama_mitra)
                ->default('-'),

            Tables\Columns\TextColumn::make('link_dokumen')
                ->label('Dokumen')
                ->formatStateUsing(fn ($state) => $state && $state !== '-' ? 'Lihat' : '-')
                ->url(fn($state) => $state && $state !== '-' ? route('view-dokumen', ['path' => $state]) : null)
                ->openUrlInNewTab()
                ->badge()
                ->icon(fn ($state) => $state && $state !== '-' ? 'heroicon-o-eye' : null)
                ->color(fn ($state) => $state && $state !== '-' ? 'primary' : 'gray')
                ->extraAttributes(fn ($state) => $state && $state !== '-' ? [
                    'style' => 'cursor: pointer; transition: opacity 0.2s;',
                    'onmouseover' => "this.style.opacity='0.6'",
                    'onmouseout' => "this.style.opacity='1'",
                ] : []),

            Tables\Columns\TextColumn::make('nomor_dokumen')
                ->label('Nomor Dokumen')
                ->searchable()
                ->default('-'),

            Tables\Columns\TextColumn::make('tahun')
                ->label('Tahun')
                ->sortable()
                ->default('-')
                ->alignCenter(),

            Tables\Columns\TextColumn::make('tanggal_awal')
                ->label('Tgl. Awal')
                ->date('d/m/Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('tanggal_akhir')
                ->label('Tgl. Akhir')
                ->date('d/m/Y')
                ->sortable(),

            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->getStateUsing(fn (Model $record) => $record->status)
                ->colors([
                    'success' => 'AKTIF',
                    'danger'  => 'BERAKHIR',
                    'warning' => fn ($state) => ! in_array($state, ['AKTIF', 'BERAKHIR']),
                ]),
        ])

        ->paginated([10, 25, 50, 100])
        ->filters([

    Tables\Filters\SelectFilter::make('negara')
        ->label('Negara')
        ->relationship('mitra.negara', 'nama_negara')
        ->searchable()
        ->preload(),
        
    Tables\Filters\SelectFilter::make('status')
        ->label('Status')
        ->options([
            'AKTIF' => 'Aktif',
            'AKAN BERAKHIR' => 'Akan Berakhir',
            'BERAKHIR' => 'Berakhir',
        ])
        ->query(function (Builder $query, array $data): Builder {

            return match ($data['value'] ?? null) {

                'AKTIF' => $query->where(function ($q) {
                    $q->whereNull('tanggal_akhir')
                        ->orWhereDate('tanggal_akhir', '>', now()->addMonth());
                }),

                'AKAN BERAKHIR' => $query
                    ->whereDate('tanggal_akhir', '>=', now())
                    ->whereDate('tanggal_akhir', '<=', now()->addMonth()),

                'BERAKHIR' => $query
                    ->whereDate('tanggal_akhir', '<', now()),

                default => $query,
            };
        }),
])
        ->actions([
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ])
        ->defaultSort('created_at', 'desc')
        ->striped();
}

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail MoA (Luar Negeri)')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('prodis.nama_prodi')
                        ->label('Program Studi')
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->prodis->pluck('nama_prodi')->unique()->all())
                        ->default('-')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('nomor_polinema')
    ->label('Nomor Polinema')
    ->getStateUsing(function ($record) {
        if (!$record->nomor_dokumen) return null;
        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
        if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
            $parts = explode(' ', $record->nomor_dokumen, 2);
        }
        return trim($parts[0] ?? '-');
    })->default('-'),

\Filament\Infolists\Components\TextEntry::make('nomor_mitra')
    ->label('Nomor Mitra')
    ->getStateUsing(function ($record) {
        if (!$record->nomor_dokumen) return null;
        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
        if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
            $parts = explode(' ', $record->nomor_dokumen, 2);
        }
        return trim($parts[1] ?? '-');
    })->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tahun')->label('Tahun')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_awal')->label('Tgl. Berlaku')->date('d/m/Y'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_akhir')->label('Tgl. Berakhir')->date('d/m/Y'),
                    \Filament\Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                        ->getStateUsing(fn (Model $record) => $record->status)
                        ->colors([
                            'AKTIF' => 'success',
                            'BERAKHIR' => 'danger',
                            'AKAN BERAKHIR' => 'warning',
                        ]),

                    \Filament\Infolists\Components\TextEntry::make('link_dokumen')
                        ->label('Link Dokumen')
                        ->url(fn($state) => $state && $state !== '-' ? route('view-dokumen', ['path' => $state]) : null)
                        ->openUrlInNewTab()
                        ->default('-')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('link_perbaikan')->label('Link Perbaikan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('bukti_kegiatan')->label('Bukti Kegiatan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMoas::route('/'),
            'create' => Pages\CreateMoa::route('/create'),
            'view'  => Pages\ViewMoa::route('/{record}'),
            'edit'  => Pages\EditMoa::route('/{record}/edit'),
        ];
    }
}
