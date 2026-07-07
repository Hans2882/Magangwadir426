<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocResource\Pages;
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

class LocResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data LoC';

    protected static ?string $modelLabel = 'Data LoC';

    protected static ?string $pluralModelLabel = 'Data LoC';

    protected static ?string $slug = 'data-loc';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis', 'Luar Negeri')
            ->where('jenis_dokumen_id', 6);
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
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(6),
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
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->judul),
                Tables\Columns\ViewColumn::make('link_dokumen')
                    ->label('Dokumen')
                    ->view('filament.tables.columns.link-dokumen'),
                Tables\Columns\TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->mitra?->nama_mitra)
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
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detail LoC (Luar Negeri)')
                ->schema([
                    Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
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
            'index' => Pages\ListLocs::route('/'),
            'view'  => Pages\ViewLoc::route('/{record}'),
        ];
    }
}
