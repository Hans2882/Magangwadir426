<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LocResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data LoC';

    protected static ?string $modelLabel = 'Data LoC';

    protected static ?string $pluralModelLabel = 'Data LoC';

    protected static ?string $slug = 'data-loc';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis', 'Luar Negeri')
            ->where('jenis_dokumen_id', 6);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('judul')->label('Judul')->maxLength(255),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra', fn (Builder $query) => $query->where('negara_id', '>=', 1))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Hidden::make('jenis')->default('Luar Negeri'),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(6),
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
            Forms\Components\TextInput::make('nomor_dokumen')->label('Nomor Dokumen')->maxLength(200),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Berlaku')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Akhir')->required(fn ($get) => $get('status_workflow') === 'Selesai'),
            Forms\Components\TextInput::make('link_perbaikan')->label('Link Perbaikan')->url()->maxLength(500),
            Forms\Components\TextInput::make('bukti_kegiatan')->label('Bukti Kegiatan')->url()->maxLength(500),
            Forms\Components\TextInput::make('link_dokumen')->label('Link Dokumen')->url()->maxLength(500)->required(fn ($get) => $get('status_workflow') === 'Selesai'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status_workflow', 'Selesai'))
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
            \Filament\Schemas\Components\Section::make('Detail LoC (Luar Negeri)')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('judul')->label('Judul')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('mitra.nama_mitra')->label('Nama Mitra')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('nomor_dokumen')->label('Nomor Dokumen')->default('-'),
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
                    \Filament\Infolists\Components\TextEntry::make('link_dokumen')->label('Link Dokumen')->url(fn($state) => $state !== '-' ? $state : null)->default('-')->columnSpanFull(),
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
            'index' => Pages\ListLocs::route('/'),
            'create' => Pages\CreateLoc::route('/create'),
            'view'  => Pages\ViewLoc::route('/{record}'),
            'edit'  => Pages\EditLoc::route('/{record}/edit'),
        ];
    }
}
