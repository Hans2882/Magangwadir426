<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;

class StatusKerjasamaChart extends ChartWidget
{
    protected static ?string $heading = 'Status Kerjasama';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $aktif = Kerjasama::whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '>=', now())->count();
        $habis = Kerjasama::whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '<', now())->count();
        $lainnya = Kerjasama::whereNull('tanggal_akhir')->count();

        $total = $aktif + $habis + $lainnya ?: 1;

        return [
            'datasets' => [
                [
                    'data'            => [$aktif, $habis, $lainnya],
                    'backgroundColor' => ['#c0392b', '#e67e22', '#f39c12'],
                    'borderColor'     => '#ffffff',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => [
                'Aktif: '      . number_format($aktif   / $total * 100, 2) . '%',
                'Habis: '      . number_format($habis   / $total * 100, 2) . '%',
                'Lainnya: '   . number_format($lainnya  / $total * 100, 2) . '%',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels'   => ['font' => ['size' => 12], 'padding' => 15],
                ],
            ],
        ];
    }
}
