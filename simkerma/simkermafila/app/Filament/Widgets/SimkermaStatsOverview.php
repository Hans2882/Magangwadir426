<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use App\Models\Mitra;
    
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimkermaStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $mitraDN = Mitra::whereNull('negara_id')->orWhere('negara_id', '<', 1)->count();
        $mitraLN = Mitra::where('negara_id', '>=', 1)->count();
        $jumlahMitra = $mitraDN + $mitraLN;
        $jumlahKerjasama = Kerjasama::count();
        $mouCount = Kerjasama::where('jenis_dokumen_id', 1)->count();
        $pksCount = Kerjasama::where('jenis_dokumen_id', 3)->count();
        $iaCount  = Kerjasama::where('jenis_dokumen_id', 4)->count();

        return [
            Stat::make('Jumlah Mitra', number_format($jumlahMitra))
                ->description($mitraDN . ' DN · ' . $mitraLN . ' LN')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Jumlah Kerjasama', number_format($jumlahKerjasama))
                ->description('MoU: ' . $mouCount . ' · PKS: ' . $pksCount . ' · IA: ' . $iaCount)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Aktif', (function () {
                return number_format(Kerjasama::whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '>=', now())->count());
            })())
                ->description('Kerjasama berstatus Aktif')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Berakhir', (function () {
                return number_format(Kerjasama::whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '<', now())->count());
            })())
                ->description('Kerjasama sudah berakhir')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
