<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

use Livewire\Attributes\On;

class KerjasamaMatiBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Kerjasama Mati per Bulan';

    protected static ?string $description = 'MoU, PKS, dan IA yang berakhir per bulan (termasuk proyeksi 1 tahun ke depan untuk MoU)';

    protected static ?int $sort = 7;

    protected static ?string $maxHeight = '340px';

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

        $datasets = [];
        $documentTypes = [
            1 => ['label' => 'MoU mati', 'color' => '#dc2626'],
            3 => ['label' => 'PKS mati', 'color' => '#7c3aed'],
            4 => ['label' => 'IA mati', 'color' => '#ea580c'],
        ];

        $periodStart = Carbon::create($startYear, 1, 1)->startOfMonth();
        $periodEnd = Carbon::create($endYear, 12, 1)->endOfMonth();

        if ($startYear <= now()->year && $endYear >= now()->year) {
            $projectionEnd = now()->copy()->addYear()->endOfMonth();
            if ($periodEnd->lt($projectionEnd)) {
                $periodEnd = $projectionEnd;
            }
        }

        $labels = [];
        $monthPoints = [];
        $current = $periodStart->copy();
        while ($current->lte($periodEnd)) {
            $labels[] = $this->monthLabel($current->month) . ' ' . $current->year;
            $monthPoints[] = ['year' => $current->year, 'month' => $current->month];
            $current->addMonth();
        }

        foreach ($documentTypes as $documentTypeId => $config) {
            $monthlyData = [];

            foreach ($monthPoints as $point) {
                $monthlyData[] = Kerjasama::query()
                    ->where('jenis_dokumen_id', $documentTypeId)
                    ->whereNotNull('tanggal_akhir')
                    ->whereYear('tanggal_akhir', $point['year'])
                    ->whereMonth('tanggal_akhir', $point['month'])
                    ->count();
            }

            $datasets[] = [
                'label' => $config['label'],
                'data' => $monthlyData,
                'borderColor' => $config['color'],
                'backgroundColor' => $this->hexToRgba($config['color'], 0.16),
                'pointBackgroundColor' => $config['color'],
                'pointBorderColor' => '#ffffff',
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
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
                ],
            ],
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
