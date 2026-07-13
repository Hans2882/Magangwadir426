<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Exports\KerjasamaExport;
use App\Filament\Resources\IaResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListIas extends ListRecords
{
    protected static string $resource = IaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new KerjasamaExport([4]),
                    'Data_IA.xlsx'
                )),

            Actions\CreateAction::make()
                ->label('Tambah IA')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Dalam Negeri' => Tab::make()
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Dalam Negeri')),

            'Luar Negeri' => Tab::make()
                ->icon('heroicon-o-globe-americas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Luar Negeri')),

            'All' => Tab::make()
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}