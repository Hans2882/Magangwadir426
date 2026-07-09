<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IaResource\Pages;
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

class IaResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data IA';

    protected static ?string $modelLabel = 'Data IA';

    protected static ?string $pluralModelLabel = 'Data IA';

    protected static ?string $slug = 'data-ia';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra', 'prodis'])
            ->where('jenis_dokumen_id', 4); // 4 = IA
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
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
            ->recordAction(null)
            ->recordUrl(fn ($record) => Pages\ViewIa::getUrl([$record->id]))
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
                        'warning' => fn ($state) => !in_array($state, ['AKTIF', 'HABIS']),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('prodis')
                    ->label('Program Studi')
                    ->relationship('prodis', 'nama_prodi')
                    ->multiple()
                    ->searchable()
                    ->preload(),
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
            Infolists\Components\Section::make('Detail IA')
                ->schema([
                    Infolists\Components\TextEntry::make('jenis')
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
                    Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    Infolists\Components\TextEntry::make('prodis')
    ->label('Program Studi')
    ->badge()
    ->getStateUsing(function ($record) {
        return $record->prodis
            ->pluck('nama_prodi')
            ->unique()
            ->implode(', ');
    })
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
            'index' => Pages\ListIas::route('/'),
            'create' => Pages\CreateIa::route('/create'),
            'view'  => Pages\ViewIa::route('/{record}'),
            'edit'  => Pages\EditIa::route('/{record}/edit'),
        ];
    }
}
