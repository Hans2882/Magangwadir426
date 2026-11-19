<?php

namespace App\Filament\Widgets;

use App\Models\JenisDokumen;
use App\Models\Kerjasama;
use App\Models\Mitra;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimkermaStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return ! $user?->isMahasiswa() && ! $user?->isDosen();
    }

    protected function getStats(): array
    {
        $mitraDN = Mitra::whereNull('negara_id')->orWhere('negara_id', '<', 1)->count();
        $mitraLN = Mitra::where('negara_id', '>=', 1)->count();
        $jumlahMitra = $mitraDN + $mitraLN;

        $allJenis = JenisDokumen::all()->keyBy('id');

        $docStats = Kerjasama::query()
            ->selectRaw("
                jenis_dokumen_id,
                COUNT(*) as total_count,
                SUM(CASE WHEN tanggal_akhir IS NOT NULL AND tanggal_akhir >= CURRENT_DATE() THEN 1 ELSE 0 END) as aktif_count,
                SUM(CASE WHEN tanggal_akhir IS NOT NULL AND tanggal_akhir < CURRENT_DATE() THEN 1 ELSE 0 END) as berakhir_count
            ")
            ->groupBy('jenis_dokumen_id')
            ->get();

        $priorityOrder = [
            'MoU' => 1,
            'MoA' => 2,
            'PKS' => 3,
            'IA' => 4,
            'SPK' => 5,
            'LoI' => 6,
            'LoC' => 7,
            'Case Study' => 8,
            'Press Release' => 9,
        ];

        $formatBreakdown = function ($field) use ($docStats, $allJenis, $priorityOrder) {
            $items = [];
            foreach ($docStats as $row) {
                $count = (int) $row->{$field};
                if ($count > 0) {
                    $nama = $allJenis->get($row->jenis_dokumen_id)?->nama 
                        ?? ($row->jenis_dokumen_id ? "ID:{$row->jenis_dokumen_id}" : 'Lainnya');
                    $items[] = [
                        'nama' => $nama,
                        'count' => $count,
                        'priority' => $priorityOrder[$nama] ?? 99,
                    ];
                }
            }

            usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);

            $parts = array_map(fn($item) => "{$item['nama']}: " . number_format($item['count']), $items);
            return empty($parts) ? '-' : implode(' · ', $parts);
        };

        $totalKerjasama = (int) $docStats->sum('total_count');
        $totalAktif = (int) $docStats->sum('aktif_count');
        $totalBerakhir = (int) $docStats->sum('berakhir_count');

        return [
            Stat::make('Jumlah Mitra', number_format($jumlahMitra))
                ->description($mitraDN . ' DN · ' . $mitraLN . ' LN')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Jumlah Kerjasama', number_format($totalKerjasama))
                ->description($formatBreakdown('total_count'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Aktif', number_format($totalAktif))
                ->description($formatBreakdown('aktif_count'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Berakhir', number_format($totalBerakhir))
                ->description($formatBreakdown('berakhir_count'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
