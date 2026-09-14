<?php

namespace App\Filament\Resources\UsulanKerjasamas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsulanKerjasamasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Diusulkan Oleh')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('usulan_nama_mitra')
                    ->label('Mitra Usulan Baru')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('created_at')
                    ->label('Tanggal Usulan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('download_dokumen')
                    ->label('Download Dokumen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (\App\Models\UsulanKerjasama $record): string => $record->dokumen_pendukung ? route('view-dokumen', ['path' => $record->dokumen_pendukung]) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (\App\Models\UsulanKerjasama $record): bool => (bool)$record->dokumen_pendukung),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
