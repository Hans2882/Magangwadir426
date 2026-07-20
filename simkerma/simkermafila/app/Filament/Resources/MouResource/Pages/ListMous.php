<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Exports\KerjasamaExport;
use App\Filament\Resources\MouResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListMous extends ListRecords
{
    protected static string $resource = MouResource::class;

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
                        'Data_MoU.xlsx'
                    );
                }),

            Actions\CreateAction::make()
                ->label('Tambah Kerja Sama')
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