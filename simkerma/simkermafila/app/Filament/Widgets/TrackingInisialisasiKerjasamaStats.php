<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackingInisialisasiKerjasamaStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $baseQuery = Kerjasama::query();

        $draft = (clone $baseQuery)
            ->where('status_workflow', 'Draft')
            ->count();

        $waiting = (clone $baseQuery)
            ->whereIn('status_workflow', ['Menunggu TTD Mitra', 'TTD Mitra'])
            ->count();

        $selesai = (clone $baseQuery)
            ->where('status_workflow', 'Selesai')
            ->count();

        $akanBerakhir = (clone $baseQuery)
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '>=', now()->startOfDay())
            ->whereDate('tanggal_akhir', '<=', now()->addMonth()->endOfDay())
            ->count();

        $total = $baseQuery->count();

        return [
            Stat::make('Total Dokumen', number_format($total))
                ->description('Semua data kerjasama')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Draft', number_format($draft))
                ->description('Sedang disusun')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color('gray'),

            Stat::make('Menunggu TTD Mitra', number_format($waiting))
                ->description('Dokumen menunggu persetujuan mitra')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Selesai', number_format($selesai))
                ->description('Dokumen telah selesai')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Akan Berakhir', number_format($akanBerakhir))
                ->description('Dokumen berakhir dalam 1 bulan')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
