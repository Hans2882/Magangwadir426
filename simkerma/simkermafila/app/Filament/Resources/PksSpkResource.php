<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PksSpkResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\MouResource;
use Filament\Schemas\Components\Utilities\Set;

class PksSpkResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data PKS & SPK';

    protected static ?string $modelLabel = 'Data PKS & SPK';

    protected static ?string $pluralModelLabel = 'Data PKS & SPK';

    protected static ?string $slug = 'data-pks-spk';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // 3 = PKS, 5 = SPK
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis', 'Dalam Negeri')
            ->whereIn('jenis_dokumen_id', [3, 5]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
    ->label('Nama Mitra')
    ->relationship(
        'mitra',
        'nama_mitra',
        fn (Builder $query) => $query->where(fn ($q) =>
            $q->whereNull('negara_id')
              ->orWhere('negara_id', '<', 1)
        )
    )
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(function ($state, Set $set) {

        $mou = \App\Models\Kerjasama::query()
            ->where('mitra_id', $state)
            ->where('jenis_dokumen_id', 1) // MoU
            ->latest('tanggal_awal')        // ambil MoU terbaru
            ->first();

        $set('parent_id', $mou?->id);
    })
    ->required(),
            Forms\Components\Select::make('parent_id')
                ->label('Referensi MoU')
                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                    $mitraId = $get('mitra_id');
                    if (! $mitraId) {
                        return [];
                    }
                    return \App\Models\Kerjasama::query()
                        ->where('mitra_id', '=', $mitraId, 'and')
                        ->where('jenis_dokumen_id', '=', 1, 'and') // 1 is usually MoU, but we can also filter by null parent_id or just all MoUs. Let's just fetch MoUs.
                        ->pluck('judul', 'id');
                })
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('bidang_id')
                ->label('Bidang Kerjasama')
                ->relationship('bidang', 'bidang_kerjasama')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('status_workflow')
                ->label('Status Proses')
                ->options([
                    'Draft' => 'Draft (Sedang Disusun)',
                    'Review Internal' => 'Review Internal',
                    'Menunggu TTD Mitra' => 'Menunggu TTD Mitra',
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
            Forms\Components\Select::make('prodis')
                ->label('Program Studi')
                ->relationship('prodis', 'nama_prodi')
                ->multiple()
                ->preload()
                ->searchable(),
            Forms\Components\Hidden::make('jenis')->default('Dalam Negeri'),
            Forms\Components\Select::make('jenis_dokumen_id')
                ->label('Jenis Dokumen')
                ->options([3 => 'PKS', 5 => 'SPK'])
                ->required(),
            Forms\Components\TextInput::make('nomor_dokumen_polinema')
    ->label('Nomor PKS/SPK Polinema')
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
                $fail('Nomor PKS/SPK Polinema sudah digunakan.');
            }
        };
    }),

Forms\Components\TextInput::make('nomor_dokumen_mitra')
    ->label('Nomor PKS/SPK Mitra')
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
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas PKS/SPK')
                ->required(fn ($get) => $get('status_workflow') === 'Selesai')
                ->disk('google')
                ->directory(function (callable $get) {
                    $base = $get('jenis_dokumen_id') == 3 ? 'PKS' : 'SPK';
                    return $base . '/' . date('Y/m/d');
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, callable $get): string {
                    $base = $get('jenis_dokumen_id') == 3 ? 'PKS' : 'SPK';
                    $dir = $base . '/' . date('Y/m/d');
                    
                    try {
                        $existingFiles = \Illuminate\Support\Facades\Storage::disk('google')->files($dir);
                        $count = count($existingFiles) + 1;
                    } catch (\Exception $e) {
                        $count = 1;
                    }
                    
                    $sequence = sprintf('%03d', $count);
                    $type = $get('jenis_dokumen_id') == 3 ? 'PKS' : 'SPK';
                    $originalName = $file->getClientOriginalName();
                    
                    return "{$sequence}_{$type}_{$originalName}";
                })
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenisDokumen.nama')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PKS' => 'primary',
                        'SPK' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
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
                    ] : [])
                    ->default('-'),
                Tables\Columns\TextColumn::make('prodis.nama_prodi')
                    ->label('Program Studi')
                    ->badge()
                    ->searchable()
                    ->default('-'),
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
                        'warning' => fn ($state) => !in_array($state, ['AKTIF', 'BERAKHIR']),
                    ]),
            ])
            ->paginated([10, 25, 50, 100])
            ->filters([
    Tables\Filters\SelectFilter::make('jenis_dokumen_id')
        ->label('Jenis Dokumen')
        ->options([
            3 => 'PKS',
            5 => 'SPK',
        ]),

    Tables\Filters\SelectFilter::make('prodis')
        ->label('Program Studi')
        ->relationship('prodis', 'nama_prodi')
        ->multiple()
        ->searchable()
        ->preload(),
        
    Tables\Filters\SelectFilter::make('bidang')
        ->label('Bidang Kerjasama')
        ->relationship('bidang', 'bidang_kerjasama')
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

                'MAU HABIS' => $query
                    ->whereDate('tanggal_akhir', '>=', now())
                    ->whereDate('tanggal_akhir', '<=', now()->addMonth()),

                'HABIS' => $query
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
            \Filament\Schemas\Components\Section::make('Detail PKS / SPK')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('jenisDokumen.nama')->label('Jenis Dokumen')->badge()
                        ->color(fn ($state) => match ($state) {
                            'PKS' => 'primary',
                            'SPK' => 'info',
                            default => 'gray',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('bidang.bidang_kerjasama')
                        ->label('Bidang Kerjasama')
                        ->badge()
                        ->default('-')
                        ->columnSpanFull(),
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

            Section::make('Hubungan Dokumen')
    ->schema([

        TextEntry::make('parent.jenisDokumen.nama')
            ->label('Jenis')
            ->badge()
            ->default('-'),

        TextEntry::make('parent.judul')
            ->label('Dokumen Induk')
            ->default('-')
            ->url(function ($record) {
                if (! $record->parent) {
                    return null;
                }

                return MouResource::getUrl('view', [
                    'record' => $record->parent,
                ]);
            })
            ->openUrlInNewTab(false)
            ->color('primary'),

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
            'index' => Pages\ListPksSpks::route('/'),
            'create' => Pages\CreatePksSpk::route('/create'),
            'view'  => Pages\ViewPksSpk::route('/{record}'),
            'edit'  => Pages\EditPksSpk::route('/{record}/edit'),
        ];
    }
}
