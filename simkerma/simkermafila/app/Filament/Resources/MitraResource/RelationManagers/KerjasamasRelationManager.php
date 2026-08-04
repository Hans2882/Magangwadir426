<?php

namespace App\Filament\Resources\MitraResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class KerjasamasRelationManager extends RelationManager
{
    protected static string $relationship = 'kerjasamas';

    protected static ?string $title = 'Daftar Kerjasama';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
            ->defaultSort('tanggal_awal', 'desc')
            ->columns([
    Tables\Columns\TextColumn::make('judul')
        ->label('Judul')
        ->searchable()
        ->sortable(),

    Tables\Columns\TextColumn::make('jenisDokumen.nama')
        ->label('Jenis Dokumen')
        ->badge()
        ->sortable(),

    Tables\Columns\TextColumn::make('nomor_dokumen')
        ->label('Nomor Dokumen')
        ->searchable(),

    Tables\Columns\TextColumn::make('bidang.bidang_kerjasama')
        ->label('Bidang')
        ->default('-')
        ->wrap(),

    Tables\Columns\TextColumn::make('tanggal_awal')
        ->label('Mulai')
        ->date('d M Y')
        ->sortable(),

    Tables\Columns\TextColumn::make('tanggal_akhir')
        ->label('Berakhir')
        ->date('d M Y')
        ->sortable(),

    Tables\Columns\TextColumn::make('status')
        ->badge()
        ->color(fn (string $state): string => match ($state) {
            'AKTIF' => 'success',
            'AKAN BERAKHIR' => 'warning',
            'BERAKHIR' => 'danger',
            default => 'gray',
        }),
])
            ->filters([
    Tables\Filters\SelectFilter::make('jenis_dokumen_id')
        ->label('Jenis Dokumen')
        ->relationship('jenisDokumen', 'nama')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('bidang_id')
        ->label('Bidang')
        ->relationship('bidang', 'bidang_kerjasama')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('jenis')
        ->label('Jenis Kerjasama')
        ->options([
            'Dalam Negeri' => 'Dalam Negeri',
            'Luar Negeri' => 'Luar Negeri',
        ]),

    Tables\Filters\Filter::make('status')
        ->form([
            \Filament\Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'AKTIF' => 'Aktif',
                    'AKAN BERAKHIR' => 'Akan Berakhir',
                    'BERAKHIR' => 'Berakhir',
                ]),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return match ($data['status'] ?? null) {
                'AKTIF' => $query->whereDate('tanggal_akhir', '>', now()->addMonth()),

                'AKAN BERAKHIR' => $query
                    ->whereDate('tanggal_akhir', '>=', now())
                    ->whereDate('tanggal_akhir', '<=', now()->addMonth()),

                'BERAKHIR' => $query
                    ->whereDate('tanggal_akhir', '<', now()),

                default => $query,
            };
        }),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
    public function infolist(Schema $schema): Schema
{
    return $schema
        ->components([
            TextEntry::make('judul')
                ->label('Judul'),

            TextEntry::make('jenisDokumen.nama')
                ->label('Jenis Dokumen'),

            TextEntry::make('nomor_dokumen')
                ->label('Nomor Dokumen'),

            TextEntry::make('mitra.nama_mitra')
                ->label('Mitra'),

            TextEntry::make('bidang.bidang_kerjasama')
                ->label('Bidang'),

            TextEntry::make('tanggal_awal')
                ->label('Tanggal Mulai')
                ->date('d M Y'),

            TextEntry::make('tanggal_akhir')
                ->label('Tanggal Berakhir')
                ->date('d M Y'),

            TextEntry::make('status')
                ->badge(),

            TextEntry::make('link_dokumen')
                ->label('Dokumen')
                ->url(fn ($state) => $state)
                ->openUrlInNewTab(),
        ]);
}
}