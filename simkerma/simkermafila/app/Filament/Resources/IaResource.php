<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IaResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IaResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data IA';

    protected static ?string $modelLabel = 'Data IA';

    protected static ?string $pluralModelLabel = 'Data IA';

    protected static ?string $slug = 'data-ia';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra', 'prodis'])
            ->where('jenis_dokumen_id', 4); // 4 = IA
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255)->required(),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('bidang_id')
                ->label('Bidang Kerjasama')
                ->relationship('bidang', 'bidang_kerjasama')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('prodis')
                ->label('Program Studi')
                ->relationship('prodis', 'nama_prodi')
                ->multiple()
                ->preload()
                ->searchable(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(4),
            Forms\Components\Select::make('jenis')
                ->label('Cakupan (DN/LN)')
                ->options([
                    'Dalam Negeri' => 'Dalam Negeri',
                    'Luar Negeri' => 'Luar Negeri',
                ])
                ->required()
                ->default('Dalam Negeri'),
            Forms\Components\TextInput::make('nomor_dokumen_polinema')
    ->label('Nomor IA Polinema')
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

            $query = \App\Models\Kerjasama::where(
                'nomor_dokumen',
                'like',
                $value . "\n%"
            );

            if ($record) {
                $query->where('id', '!=', $record->id);
            }

            if ($query->exists()) {
                $fail('Nomor IA Polinema sudah digunakan.');
            }
        };
    }),

Forms\Components\TextInput::make('nomor_dokumen_mitra')
    ->label('Nomor IA Mitra')
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
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Berlaku'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Akhir'),
            Forms\Components\TextInput::make('link_perbaikan')->label('Link Perbaikan')->url()->maxLength(500),
            Forms\Components\TextInput::make('bukti_kegiatan')->label('Bukti Kegiatan')->url()->maxLength(500),
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas IA')
                ->disk('google')
                ->directory(function (callable $get) {
                    return $get('jenis') === 'Luar Negeri' ? 'IA LN' : 'IA';
                })
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, ?\Illuminate\Database\Eloquent\Model $record): string {
                    $id = $record ? $record->id : (\App\Models\Kerjasama::max('id') + 1);
                    $type = 'IA';
                    $originalName = $file->getClientOriginalName();
                    return "{$id}_{$type}_{$originalName}";
                })
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                ] : [])
                ->default('-'),

            Tables\Columns\TextColumn::make('prodis')
    ->label('Program Studi')
    ->badge()
    ->getStateUsing(function ($record) {
        return $record->prodis
            ->pluck('nama_prodi')
            ->unique()
            ->implode(', ');
    })
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
                    'danger'  => 'HABIS',
                    'warning' => fn ($state) => ! in_array($state, ['AKTIF', 'HABIS']),
                ]),
        ])
        ->paginated([10, 25, 50, 100])
        ->filters([
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

    Tables\Filters\SelectFilter::make('negara')
        ->label('Negara')
        ->relationship('mitra.negara', 'nama_negara')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('status')
        ->label('Status')
        ->options([
            'AKTIF' => 'Aktif',
            'MAU HABIS' => 'Mau Habis',
            'HABIS' => 'Habis',
        ])
        ->query(function (Builder $query, array $data): Builder {

            return match ($data['value'] ?? null) {

                'AKTIF' => $query->where(function ($q) {
                    $q->whereNull('tanggal_akhir')
                      ->orWhereDate('tanggal_akhir', '>', today()->addMonth());
                }),

                'MAU HABIS' => $query
                    ->whereDate('tanggal_akhir', '>=', today())
                    ->whereDate('tanggal_akhir', '<=', today()->addMonth()),

                'HABIS' => $query
                    ->whereDate('tanggal_akhir', '<', today()),

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
            Filament\Schemas\Components\Section::make('Detail IA')
                ->schema([
                    Filament\Schemas\Components\Text::make('jenis')
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
                    Filament\Schemas\Components\Text::make('judul')->label('Judul')->columnSpanFull(),
                    Filament\Schemas\Components\Text::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    Filament\Schemas\Components\Text::make('bidang.bidang_kerjasama')
                        ->label('Bidang Kerjasama')
                        ->badge()
                        ->default('-')
                        ->columnSpanFull(),
                    Filament\Schemas\Components\Text::make('prodis.nama_prodi')
                        ->label('Program Studi')
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->prodis->pluck('nama_prodi')->unique()->all())
                        ->default('-')
                        ->columnSpanFull(),
                    Filament\Schemas\Components\Text::make('nomor_polinema')
    ->label('Nomor Polinema')
    ->getStateUsing(function ($record) {
        if (!$record->nomor_dokumen) return null;
        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
        if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
            $parts = explode(' ', $record->nomor_dokumen, 2);
        }
        return trim($parts[0] ?? '-');
    })->default('-'),

Filament\Schemas\Components\Text::make('nomor_mitra')
    ->label('Nomor Mitra')
    ->getStateUsing(function ($record) {
        if (!$record->nomor_dokumen) return null;
        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));
        if (count($parts) === 1 && strpos($record->nomor_dokumen, ' ') !== false) {
            $parts = explode(' ', $record->nomor_dokumen, 2);
        }
        return trim($parts[1] ?? '-');
    })->default('-'),
                    Filament\Schemas\Components\Text::make('tahun')->label('Tahun')->default('-'),
                    Filament\Schemas\Components\Text::make('tanggal_awal')->label('Tgl. Berlaku')->date('d/m/Y'),
                    Filament\Schemas\Components\Text::make('tanggal_akhir')->label('Tgl. Berakhir')->date('d/m/Y'),
                    Filament\Schemas\Components\Text::make('status')->label('Status')->badge()
                        ->getStateUsing(fn (Model $record) => $record->status)
                        ->color(fn ($state) => match($state) {
                            'AKTIF' => 'success',
                            'HABIS' => 'danger',
                            default => 'warning',
                        }),
                    Filament\Schemas\Components\Text::make('link_dokumen')
                        ->label('Link Dokumen')
                        ->url(fn($state) => $state && $state !== '-' ? route('view-dokumen', ['path' => $state]) : null)
                        ->openUrlInNewTab()
                        ->default('-')
                        ->columnSpanFull(),
                    Filament\Schemas\Components\Text::make('link_perbaikan')->label('Link Perbaikan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
                    Filament\Schemas\Components\Text::make('bukti_kegiatan')->label('Bukti Kegiatan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
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
            'index' => Pages\ListIas::route('/'),
            'create' => Pages\CreateIa::route('/create'),
            'view'  => Pages\ViewIa::route('/{record}'),
            'edit'  => Pages\EditIa::route('/{record}/edit'),
        ];
    }
}
