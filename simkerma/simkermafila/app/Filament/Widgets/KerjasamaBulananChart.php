<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;

use Livewire\Attributes\On;

class KerjasamaBulananChart extends ChartWidget
{
    protected ?string $heading = 'Perkembangan Kerjasama per Bulan';

    protected ?string $description = 'Data bulanan untuk MoU, PKS, dan IA';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '380px';

    protected int | string | array $columnSpan = 2;

    public ?string $preset = 'this_year';
    public ?string $startYear = null;
    public ?string $endYear = null;

    #[On('filter-updated')]
    public function updateFilter($preset, $startYear = null, $endYear = null)
    {
        $this->preset = $preset;
        $this->startYear = $startYear;
        $this->endYear = $endYear;
    }

    protected function getData(): array
    {
        $preset = $this->preset;
        $startYear = now()->year;
        $endYear = now()->year;

        if ($preset === 'last_1_year') {
            $startYear = now()->year - 1;
        } elseif ($preset === 'last_5_years') {
            $startYear = now()->year - 5;
        } elseif ($preset === 'last_10_years') {
            $startYear = now()->year - 10;
        } elseif ($preset === 'all_time') {
            $startYear = 2000;
        } elseif ($preset === 'custom') {
            $startYear = (int) ($this->startYear ?? now()->year);
            $endYear = (int) ($this->endYear ?? now()->year);
        }

        if ($startYear > $endYear) {
            $temp = $startYear;
            $startYear = $endYear;
            $endYear = $temp;
        }

        $labels = [];
        $monthPoints = [];
        $current = \Carbon\Carbon::create($startYear, 1, 1)->startOfMonth();
        $end = \Carbon\Carbon::create($endYear, 12, 1)->endOfMonth();

        while ($current->lte($end)) {
            $labels[] = $this->monthLabel($current->month) . ' ' . $current->year;
            $monthPoints[] = ['year' => $current->year, 'month' => $current->month];
            $current->addMonth();
        }

        $datasets = [];
        $documentTypes = [
            1 => ['label' => 'MoU', 'color' => '#2563eb'],
            3 => ['label' => 'PKS', 'color' => '#16a34a'],
            4 => ['label' => 'IA', 'color' => '#f59e0b'],
        ];

        foreach ($documentTypes as $documentTypeId => $config) {
            $data = [];

            foreach ($monthPoints as $point) {
                $data[] = Kerjasama::query()
                    ->where('jenis_dokumen_id', $documentTypeId)
                    ->whereYear('tanggal_awal', $point['year'])
                    ->whereMonth('tanggal_awal', $point['month'])
                    ->count();
            }

            $datasets[] = [
                'label' => $config['label'],
                'data' => $data,
                'borderColor' => $config['color'],
                'backgroundColor' => $this->hexToRgba($config['color'], 0.16),
                'pointBackgroundColor' => $config['color'],
                'pointBorderColor' => '#ffffff',
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'type' => 'line',
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function monthLabel(int $month): string
    {
        return match ($month) {
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            default => 'Des',
        };
    }

    protected function hexToRgba(string $hex, float $opacity): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba($r, $g, $b, $opacity)";
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 14,
                        'font' => [
                            'size' => 12,
                            'weight' => '600',
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => '#64748b',
                        'font' => [
                            'weight' => '500',
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'color' => '#64748b',
                    ],
                    'grid' => [
                        'color' => 'rgba(15, 23, 42, 0.08)',
                    ],
                ],
            ],
        ];
    }
}
