<?php

namespace App\Filament\Resources\PksSpkResource\Pages;

use App\Exports\KerjasamaExport;
use App\Filament\Resources\PksSpkResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPksSpks extends ListRecords
{
    protected static string $resource = PksSpkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new KerjasamaExport([3, 5], 'Dalam Negeri'),
                    'Data_PKS_SPK.xlsx'
                )),

            Actions\CreateAction::make()
                ->label('Tambah PKS / SPK')
                ->icon('heroicon-o-plus'),
        ];
    }
}