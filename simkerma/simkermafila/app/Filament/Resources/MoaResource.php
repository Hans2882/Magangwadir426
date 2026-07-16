<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MoaResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MoaResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Data Kerjasama';

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra', fn (Builder $query) => $query->where('negara_id', '>=', 1))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Hidden::make('jenis')->default('Luar Negeri'),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(2),
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

            $query = \App\Models\Kerjasama::where(
                'nomor_dokumen',
                'like',
                $value . "\n%"
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
    ->dehydrateStateUsing(function (Forms\Get $get) {

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
            Forms\Components\TextInput::make('tahun')->label('Tahun')->maxLength(10),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Awal'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Akhir'),
            Forms\Components\TextInput::make('link_perbaikan')->label('Link Perbaikan')->url()->maxLength(500),
            Forms\Components\TextInput::make('bukti_kegiatan')->label('Bukti Kegiatan')->url()->maxLength(500),
            Forms\Components\FileUpload::make('link_dokumen')
                ->label('Berkas MoA')
                ->disk('google')
                ->directory('MoA')
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->preserveFilenames()
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

            Tables\Columns\TextColumn::make('nomor_polinema')
    ->label('Nomor Polinema')
    ->searchable(query: function (Builder $query, string $search) {
        $query->where('nomor_dokumen', 'like', $search . '%');
    })
    ->getStateUsing(function ($record) {

        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

        return trim($parts[0] ?? '-');
    }),

Tables\Columns\TextColumn::make('nomor_mitra')
    ->label('Nomor Mitra')
    ->getStateUsing(function ($record) {

        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

        return trim($parts[1] ?? '-');
    })
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

        ->filters([
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
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->defaultSort('created_at', 'desc')
        ->striped();
}

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detail MoA (Luar Negeri)')
                ->schema([
                    Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    Infolists\Components\TextEntry::make('prodis.nama_prodi')
                        ->label('Program Studi')
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->prodis->pluck('nama_prodi')->unique()->all())
                        ->default('-')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('nomor_polinema')
    ->label('Nomor Polinema')
    ->getStateUsing(function ($record) {

        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

        return trim($parts[0] ?? '-');
    }),

Infolists\Components\TextEntry::make('nomor_mitra')
    ->label('Nomor Mitra')
    ->getStateUsing(function ($record) {

        $parts = explode("\n", str_replace("\r", "", $record->nomor_dokumen));

        return trim($parts[1] ?? '-');
    }),
                    Infolists\Components\TextEntry::make('tahun')->label('Tahun')->default('-'),
                    Infolists\Components\TextEntry::make('tanggal_awal')->label('Tgl. Berlaku')->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('tanggal_akhir')->label('Tgl. Berakhir')->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                        ->getStateUsing(fn (Model $record) => $record->status)
                        ->color(fn ($state) => match($state) {
                            'AKTIF' => 'success',
                            'HABIS' => 'danger',
                            default => 'warning',
                        }),

                    Infolists\Components\TextEntry::make('link_dokumen')
                        ->label('Link Dokumen')
                        ->url(fn($state) => $state && $state !== '-' ? route('view-dokumen', ['path' => $state]) : null)
                        ->openUrlInNewTab()
                        ->default('-')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('link_perbaikan')->label('Link Perbaikan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('bukti_kegiatan')->label('Bukti Kegiatan')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
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
