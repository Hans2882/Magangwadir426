<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PksSpkResource\Pages;
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

class PksSpkResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data PKS & SPK';

    protected static ?string $modelLabel = 'Data PKS & SPK';

    protected static ?string $pluralModelLabel = 'Data PKS & SPK';

    protected static ?string $slug = 'data-pks-spk';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        // 3 = PKS, 5 = SPK
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis', 'Dalam Negeri')
            ->whereIn('jenis_dokumen_id', [3, 5]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra', fn (Builder $query) => $query->whereNull('negara_id')->orWhere('negara_id', '<', 1))
                ->searchable()
                ->preload()
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
            Forms\Components\TextInput::make('nomor_dokumen')->label('Nomor Dokumen')->maxLength(200),
            Forms\Components\TextInput::make('tahun')->label('Tahun')->maxLength(10),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Awal'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Akhir'),
            Forms\Components\TextInput::make('link_perbaikan')->label('Link Perbaikan')->url()->maxLength(500),
            Forms\Components\TextInput::make('bukti_kegiatan')->label('Bukti Kegiatan')->url()->maxLength(500),
            Forms\Components\TextInput::make('link_dokumen')->label('Link Dokumen')->url()->maxLength(500),
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
                        'danger'  => 'HABIS',
                        'warning' => fn ($state) => !in_array($state, ['AKTIF', 'HABIS']),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_dokumen_id')
                    ->label('Jenis Dokumen')
                    ->options([3 => 'PKS', 5 => 'SPK']),
                Tables\Filters\SelectFilter::make('prodis')
                    ->label('Program Studi')
                    ->relationship('prodis', 'nama_prodi')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detail PKS / SPK')
                ->schema([
                    Infolists\Components\TextEntry::make('jenisDokumen.nama')->label('Jenis Dokumen')->badge()
                        ->color(fn ($state) => match ($state) {
                            'PKS' => 'primary',
                            'SPK' => 'info',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    Infolists\Components\TextEntry::make('prodis.nama_prodi')
                        ->label('Program Studi')
                        ->badge()
                        ->default('-')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('nomor_dokumen')->label('Nomor Dokumen')->default('-'),
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
                    Infolists\Components\TextEntry::make('link_dokumen')->label('Link Dokumen')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
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
            'index' => Pages\ListPksSpks::route('/'),
            'create' => Pages\CreatePksSpk::route('/create'),
            'view'  => Pages\ViewPksSpk::route('/{record}'),
            'edit'  => Pages\EditPksSpk::route('/{record}/edit'),
        ];
    }
}
