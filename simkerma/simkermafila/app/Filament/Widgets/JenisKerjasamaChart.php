<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;

class JenisKerjasamaChart extends ChartWidget
{
    protected static ?string $heading = 'Jenis Kerjasama';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $mou = Kerjasama::where('jenis_dokumen_id', 1)->count();
        $pks = Kerjasama::where('jenis_dokumen_id', 3)->count();
        $ia  = Kerjasama::where('jenis_dokumen_id', 4)->count();

        $total = $mou + $pks + $ia ?: 1;

        return [
            'datasets' => [
                [
                    'data'            => [$mou, $pks, $ia],
                    'backgroundColor' => ['#c0392b', '#e67e22', '#f39c12'],
                    'borderColor'     => '#ffffff',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => [
                'MoU: ' . number_format($mou / $total * 100, 2) . '%',
                'PKS: ' . number_format($pks / $total * 100, 2) . '%',
                'IA: '  . number_format($ia  / $total * 100, 2) . '%',
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
