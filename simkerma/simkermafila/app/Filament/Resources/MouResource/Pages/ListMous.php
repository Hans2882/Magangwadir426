<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Exports\KerjasamaExport;
use App\Filament\Resources\MouResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListMous extends ListRecords
{
    protected static string $resource = MouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | API ACTION
            |--------------------------------------------------------------------------
            */

            Action::make('api')
                ->label('Lihat API')
                ->icon('heroicon-o-code-bracket')
                ->modalHeading('API Data MoU')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function () {

                    $filters = $this->tableFilters ?? [];

                    $activeTab = $this->activeTab ?? 'All';

                    return view('api.kerjasama', [
                        'endpoint' => url('/api/mou'),

                        'label' => 'MoU',

                        'filters' => array_filter([
                            'jenis' => $activeTab !== 'All'
                                ? $activeTab
                                : null,

                            'tahun' => $filters['tahun']['value']
                                ?? null,

                            'bidang_id' => $filters['bidang']['value']
                                ?? null,

                            'negara_id' => $filters['negara']['value']
                                ?? null,

                            'status' => $filters['status']['value']
                                ?? null,
                        ], function ($value) {
                            return $value !== null
                                && $value !== '';
                        }),
                    ]);
                }),

            /*
            |--------------------------------------------------------------------------
            | EXPORT EXCEL
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | CREATE
            |--------------------------------------------------------------------------
            */

            Actions\CreateAction::make()
                ->label('Tambah Kerja Sama')
                ->icon('heroicon-o-plus'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TABS
    |--------------------------------------------------------------------------
    */

    public function getTabs(): array
    {
        return [
            'Dalam Negeri' => Tab::make()
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(
                    fn (Builder $query) =>
                        $query->where('jenis', 'Dalam Negeri')
                ),

            'Luar Negeri' => Tab::make()
                ->icon('heroicon-o-globe-americas')
                ->modifyQueryUsing(
                    fn (Builder $query) =>
                        $query->where('jenis', 'Luar Negeri')
                ),

            'All' => Tab::make()
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}