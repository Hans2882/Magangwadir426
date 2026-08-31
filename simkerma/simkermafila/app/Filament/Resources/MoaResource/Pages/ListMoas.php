<?php

namespace App\Filament\Resources\MoaResource\Pages;

use App\Exports\KerjasamaExport;
use App\Filament\Resources\MoaResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListMoas extends ListRecords
{
    protected static string $resource = MoaResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery()
                        ->with([
                            'mitra',
                            'bidang',
                            'prodis',
                            'jenisDokumen',
                        ]);
                    return Excel::download(
                        new KerjasamaExport($query),
                        'Data_MoA.xlsx'
                    );
                }),

            Actions\CreateAction::make()
                ->label('Tambah MoA')
                ->icon('heroicon-o-plus'),
        ];
    }
}