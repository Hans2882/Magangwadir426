<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MouResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class MouResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data MoU';

    protected static ?string $modelLabel = 'Data MoU';

    protected static ?string $pluralModelLabel = 'Data MoU';

    protected static ?string $slug = 'data-mou';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'mitra',
                'children.jenisDokumen',
            ])
            ->where('jenis_dokumen_id', 1); // 1 = MoU
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas MoU')
                ->required(fn ($get) => $get('status_workflow') === 'Selesai')
                ->validationMessages(['required' => 'Berkas MoU wajib diunggah.'])
                ->hintAction(\App\Services\GeminiOcrService::getAutoFillAction())
                ->disk('google')
                ->directory(function (callable $get) {
                    $base = $get('jenis') === 'Luar Negeri' ? 'MoU LN' : 'MoU';
                    return $base . '/' . date('Y/m/d');
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, callable $get): string {
                    $base = $get('jenis') === 'Luar Negeri' ? 'MoU LN' : 'MoU';
                    $dir = $base . '/' . date('Y/m/d');
                    
                    try {
                        $existingFiles = \Illuminate\Support\Facades\Storage::disk('google')->files($dir);
                        $count = count($existingFiles) + 1;
                    } catch (\Exception $e) {
                        $count = 1;
                    }
                    
                    $sequence = sprintf('%03d', $count);
                    $type = 'MoU';
                    $originalName = $file->getClientOriginalName();
                    
                    return "{$sequence}_{$type}_{$originalName}";
                })
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('topdown')
                ->label('Top Down')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('judul')->label('Judul MoU')->maxLength(255)->required(),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(1),
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
            Forms\Components\Select::make('jenis')
                ->label('Cakupan (DN/LN)')
                ->options([
                    'Dalam Negeri' => 'Dalam Negeri',
                    'Luar Negeri' => 'Luar Negeri',
                ])
                ->required()
                ->default('Dalam Negeri'),
            Forms\Components\TextInput::make('nomor_dokumen_polinema')
    ->label('Nomor MoU Polinema')
    ->required()
    ->maxLength(100)
    ->dehydrated(false)
    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
        if ($record && $record->nomor_dokumen) {
            $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
            if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                $parts = explode(" ", $record->nomor_dokumen, 2);
            }
            $component->state(trim($parts[0] ?? ''));
        }
    })
    ->rule(function (?Model $record) {
        return function ($attribute, $value, $fail) use ($record) {

            $exists = \App\Models\Kerjasama::query()
                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                ->get()
                ->contains(function ($item) use ($value) {

                    $nomor = str_replace("\r", "", $item->nomor_dokumen);
                    $parts = explode("\n", $nomor);

                    // Jika data lama dipisah spasi
                    if (count($parts) === 1 && str_contains($nomor, ' ')) {
                        $parts = explode(' ', $nomor, 2);
                    }

                    return trim($parts[0] ?? '') === trim($value);
                });

            if ($exists) {
                $fail('Nomor MoU Polinema sudah digunakan.');
            }
        };
    }),
            Forms\Components\TextInput::make('nomor_dokumen_mitra')
                ->label('Nomor MoU Mitra')
                ->maxLength(100)
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
                    if ($record && $record->nomor_dokumen) {
                        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
                        if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                            $parts = explode(" ", $record->nomor_dokumen, 2);
                        }
                        $component->state(trim($parts[1] ?? ''));
                    }
                }),
            Forms\Components\Hidden::make('nomor_dokumen')
                ->dehydrateStateUsing(function (\Filament\Schemas\Components\Utilities\Get $get) {
                    $pol = trim($get('nomor_dokumen_polinema') ?? '');
                    $mit = trim($get('nomor_dokumen_mitra') ?? '');
                    if (empty($pol) && empty($mit)) return null;
                    if (empty($pol)) return $mit;
                    if (empty($mit)) return $pol;
                    return $pol . "\n" . $mit;
                }),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Berlaku')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Berakhir')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\Select::make('bidang_id')
                ->label('Bidang Kerjasama')
                ->relationship('bidang', 'bidang_kerjasama')
                ->searchable()
                ->preload()
                ->required(),
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
                    ->tooltip(fn ($record) => $record->mitra?->nama_mitra),
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
                Tables\Columns\IconColumn::make('pks')
    ->label('PKS/SPK')
    ->boolean()
    ->getStateUsing(fn ($record) =>
        $record->children->whereIn('jenis_dokumen_id', [3,5])->isNotEmpty()
    ),
                Tables\Columns\IconColumn::make('kelengkapan')
    ->label('IA')
    ->boolean()
    ->getStateUsing(fn ($record) =>
        $record->children->contains('jenis_dokumen_id', 4)
    ),
                Tables\Columns\BadgeColumn::make('status')
    ->label('Status')
    ->getStateUsing(fn (Model $record) => $record->status)
    ->colors([
        'success' => 'AKTIF',
        'warning' => 'AKAN BERAKHIR',
        'danger'  => 'BERAKHIR',
    ]),
            ])

            ->paginated([10, 25, 50, 100])
            ->filters([
    Tables\Filters\SelectFilter::make('tahun')
        ->label('Tahun')
        ->options(function () {
        return \App\Models\Kerjasama::query()
            ->where('jenis_dokumen_id', 1)
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun', 'tahun')
            ->toArray();
    }),
    Tables\Filters\SelectFilter::make('bidang')
        ->label('Bidang Kerjasama')
        ->relationship('bidang', 'bidang_kerjasama')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('negara')
        ->label('Negara')
        ->relationship('mitra.negara', 'nama_negara')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('kelengkapan')
    ->label('Kelengkapan Dokumen')
    ->options([
    'lengkap' => 'Lengkap',
    'belum_ia' => 'Belum ada IA',
    'belum_pks' => 'Belum ada PKS/SPK',
    'belum_semua' => 'Belum ada IA & PKS/SPK',
])
    ->query(function (Builder $query, array $data): Builder {

        return match ($data['value'] ?? null) {

    'lengkap' => $query
        ->whereHas('children', function ($q) {
            $q->where('jenis_dokumen_id', 4);
        })
        ->whereHas('children', function ($q) {
            $q->whereIn('jenis_dokumen_id', [3, 5]);
        }),

    'belum_ia' => $query->whereDoesntHave('children', function ($q) {
        $q->where('jenis_dokumen_id', 4);
    }),

    'belum_pks' => $query->whereDoesntHave('children', function ($q) {
        $q->whereIn('jenis_dokumen_id', [3, 5]);
    }),

    'belum_semua' => $query
        ->whereDoesntHave('children', function ($q) {
            $q->where('jenis_dokumen_id', 4);
        })
        ->whereDoesntHave('children', function ($q) {
            $q->whereIn('jenis_dokumen_id', [3, 5]);
        }),

    default => $query,
};
    }),
        
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
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail MoU')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('jenis')
                        ->label('Cakupan (DN/LN)')
                        ->default('-')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Dalam Negeri' => 'success',
                            'Luar Negeri' => 'warning',
                            default => 'gray',
                        })
                        ->icon(fn (?string $state): string => match ($state) {
                            'Dalam Negeri' => 'heroicon-o-building-office-2',
                            'Luar Negeri' => 'heroicon-o-globe-americas',
                            default => 'heroicon-o-question-mark-circle',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('bidang.bidang_kerjasama')
                        ->label('Bidang Kerjasama')
                        ->badge()
                        ->default('-')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('nomor_dokumen_polinema')
                        ->label('Nomor Polinema')
                        ->getStateUsing(function ($record) {
                            if (!$record->nomor_dokumen) return null;
                            $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
                            if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                                $parts = explode(' ', $record->nomor_dokumen, 2);
                            }
                            return trim($parts[0] ?? '');
                        })->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('nomor_dokumen_mitra')
                        ->label('Nomor Mitra')
                        ->getStateUsing(function ($record) {
                            if (!$record->nomor_dokumen) return null;
                            $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
                            if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
                                $parts = explode(' ', $record->nomor_dokumen, 2);
                            }
                            return trim($parts[1] ?? '');
                        })->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tahun')->label('Tahun')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_awal')->label('Tgl. Berlaku')->date('d/m/Y'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_akhir')->label('Tgl. Berakhir')->date('d/m/Y'),
                    \Filament\Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                        ->getStateUsing(fn (Model $record) => $record->status)
                        ->color(fn ($state) => match ($state) {
                            'AKTIF' => 'success',
                            'AKAN BERAKHIR' => 'warning',
                            'BERAKHIR' => 'danger',
                            default => 'gray',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('topdown')
                        ->label('Top Down')
                        ->html()
                        ->default('-')
                        ->columnSpanFull(),
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
        RepeatableEntry::make('children')
            ->label('PKS / SPK')
            ->state(fn ($record) => $record->children
                ->whereIn('jenis_dokumen_id', [3, 5])
                ->values())
            ->contained()
            ->grid(3)
            ->schema([

                TextEntry::make('jenisDokumen.nama')
                    ->label('Jenis'),

                TextEntry::make('judul')
                    ->url(function ($record) {
                        return \App\Filament\Resources\PksSpkResource::getUrl(
                            'view',
                            ['record' => $record]
                        );
                    })
                    ->color('primary'),

                TextEntry::make('status')
                    ->badge(),

            ]),

        RepeatableEntry::make('children')
            ->label('IA')
            ->state(fn ($record) => $record->children
                ->where('jenis_dokumen_id', 4)
                ->values())
            ->contained()
            ->grid(3)
            ->schema([

                TextEntry::make('jenisDokumen.nama')
                    ->label('Jenis'),

                TextEntry::make('judul')
                    ->url(function ($record) {
                        return \App\Filament\Resources\IaResource::getUrl(
                            'view',
                            ['record' => $record]
                        );
                    })
                    ->color('primary'),

                TextEntry::make('status')
                    ->badge(),
            ]),
    ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMous::route('/'),
            'create' => Pages\CreateMou::route('/create'),
            'view'  => Pages\ViewMou::route('/{record}'),
            'edit'  => Pages\EditMou::route('/{record}/edit'),
        ];
    }
}