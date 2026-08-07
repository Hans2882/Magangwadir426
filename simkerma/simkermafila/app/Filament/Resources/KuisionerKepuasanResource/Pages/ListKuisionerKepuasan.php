<?php

namespace App\Filament\Resources\KuisionerKepuasanResource\Pages;

use App\Filament\Resources\KuisionerKepuasanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ListKuisionerKepuasan extends ListRecords
{
    protected static string $resource = KuisionerKepuasanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableActions(): array
    {
        return [];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nama')
                ->label('Nama')
                ->searchable()
                ->sortable(),

            TextColumn::make('jabatan')
                ->label('Jabatan')
                ->searchable()
                ->sortable(),

            TextColumn::make('instansi')
                ->label('Instansi / Mitra Asal')
                ->searchable()
                ->sortable(),

            TextColumn::make('email')
                ->label('Email')
                ->sortable(),

            TextColumn::make('telepon')
                ->label('Nomor Telepon')
                ->sortable(),

            TextColumn::make('program_studi_alumni')
                ->label('Program Studi Alumni')
                ->sortable()
                ->limit(20),

            TextColumn::make('created_at')
                ->label('Dikirim Pada')
                ->date('d F Y H:i')
                ->sortable(),
        ];
    }
}
