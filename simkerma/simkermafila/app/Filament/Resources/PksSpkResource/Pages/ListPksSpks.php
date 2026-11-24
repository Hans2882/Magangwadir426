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
            /*
            |--------------------------------------------------------------------------
            | API ACTION
            |--------------------------------------------------------------------------
            */

            Action::make('api')
                ->label('Lihat API')
                ->icon('heroicon-o-code-bracket')
                ->modalHeading('API Data PKS / SPK')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function () {

                    $filters = $this->tableFilters ?? [];

                    return view('api.kerjasama', [
                        'endpoint' => url('/api/pks'),

                        'label' => 'PKS / SPK',

                        'filters' => array_filter([
                            'jenis_dokumen_id' =>
                                $filters['jenis_dokumen_id']['value']
                                ?? null,

                            'prodi_id' =>
                                $filters['prodis']['values']
                                ?? $filters['prodis']['value']
                                ?? [],

                            'bidang_id' =>
                                $filters['bidang']['value']
                                ?? null,

                            'status' =>
                                $filters['status']['value']
                                ?? null,
                        ], function ($value) {
                            return $value !== null
                                && $value !== ''
                                && $value !== [];
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
                            'jurusans',
                            'jenisDokumen',
                        ]);

                    return Excel::download(
                        new KerjasamaExport($query),
                        'Data_PKS_SPK.xlsx'
                    );
                }),

            /*
            |--------------------------------------------------------------------------
            | CREATE
            |--------------------------------------------------------------------------
            */

            Action::make('tambah_terpadu')
                ->label('Tambah Dokumen Baru')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(fn (): bool => !\Illuminate\Support\Facades\Auth::user()?->isKoorMagang())
                ->url(fn (): string => \App\Filament\Pages\TambahDokumenBaru::getUrl()),

            Actions\CreateAction::make()
                ->label('Tambah Manual PKS')
                ->color('gray')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PksSpkResource\Widgets\PksJurusanOverview::class,
        ];
    }
}