<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use App\Models\Kerjasama;
use Filament\Tables\Columns\TextColumn;

class TrackingInisialisasiKerjasama extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.tracking-inisialisasi-kerjasama';

    public function table(Table $table): Table
    {
        return $table
            ->query(Kerjasama::query()->latest())
            ->columns([
                TextColumn::make('judul')->label('Judul / Nama Dokumen')->searchable()->limit(50),
                TextColumn::make('jenisDokumen.nama')->label('Jenis')->badge(),
                TextColumn::make('mitra.nama_mitra')->label('Mitra')->searchable()->limit(30),
                TextColumn::make('tanggal_awal')->label('Tanggal')->date(),
                TextColumn::make('created_at')->label('Dibuat Pada')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('jenis_dokumen_id')
                    ->label('Jenis Dokumen')
                    ->relationship('jenisDokumen', 'nama')
                    ->preload()
                    ->searchable(),
            ]);
    }
}
