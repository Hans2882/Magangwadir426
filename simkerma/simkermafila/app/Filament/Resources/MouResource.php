<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MouResource\Pages;
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

class MouResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Data Kerjasama';

    protected static ?string $navigationLabel = 'Data MoU';

    protected static ?string $modelLabel = 'Data MoU';

    protected static ?string $pluralModelLabel = 'Data MoU';

    protected static ?string $slug = 'data-mou';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra'])
            ->where('jenis_dokumen_id', 1); // 1 = MoU
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')->label('Judul MoU')->maxLength(255)->required(),
            Forms\Components\Select::make('mitra_id')
                ->label('Nama Mitra')
                ->relationship('mitra', 'nama_mitra')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(1),
            Forms\Components\Select::make('jenis')
                ->label('Cakupan (DN/LN)')
                ->options([
                    'Dalam Negeri' => 'Dalam Negeri',
                    'Luar Negeri' => 'Luar Negeri',
                ])
                ->required()
                ->default('Dalam Negeri'),
            Forms\Components\TextInput::make('nomor_dokumen_polinema')
                ->label('Nomor Mou Polinema')
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
                }),
            Forms\Components\TextInput::make('nomor_dokumen_mitra')
                ->label('Nomor Mou Mitra')
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
                ->dehydrateStateUsing(function (Forms\Get $get) {
                    $pol = trim($get('nomor_dokumen_polinema') ?? '');
                    $mit = trim($get('nomor_dokumen_mitra') ?? '');
                    if (empty($pol) && empty($mit)) return null;
                    if (empty($pol)) return $mit;
                    if (empty($mit)) return $pol;
                    return $pol . "\n" . $mit;
                }),
            Forms\Components\DatePicker::make('tanggal_awal')->label('Tanggal Berlaku'),
            Forms\Components\DatePicker::make('tanggal_akhir')->label('Tanggal Berakhir'),
            Forms\Components\Select::make('bidang_id')
                ->label('Bidang Kerjasama')
                ->relationship('bidang', 'bidang_kerjasama')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('link_dokumen')->label('Berkas Mou (Google Drive)')->url()->maxLength(500)->columnSpanFull(),
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detail MoU')
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
            'index' => Pages\ListMous::route('/'),
            'create' => Pages\CreateMou::route('/create'),
            'view'  => Pages\ViewMou::route('/{record}'),
            'edit'  => Pages\EditMou::route('/{record}/edit'),
        ];
    }
}
