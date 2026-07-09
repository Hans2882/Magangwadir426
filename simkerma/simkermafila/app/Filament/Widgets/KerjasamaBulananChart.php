<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;

class KerjasamaBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Perkembangan Kerjasama per Bulan';

    protected static ?string $description = 'Data bulanan untuk MoU, PKS, dan IA';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '380px';

    protected int | string | array $columnSpan = 2;

    public ?string $filter = null;

    public function mount(): void
    {
        parent::mount();

        if (blank($this->filter)) {
            $this->filter = (string) now()->year;
        }
    }

    protected function getData(): array
    {
        $year = $this->filter ?? $this->getDefaultYear();

        $mouData = $this->buildMonthlyCounts($year, 1);
        $pksData = $this->buildMonthlyCounts($year, 3);
        $iaData = $this->buildMonthlyCounts($year, 4);

        return [
            'type' => 'line',
            'datasets' => [
                [
                    'label' => 'MoU',
                    'data' => $mouData,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.16)',
                    'pointBackgroundColor' => '#2563eb',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'PKS',
                    'data' => $pksData,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.16)',
                    'pointBackgroundColor' => '#16a34a',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'IA',
                    'data' => $iaData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.16)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        ];
    }

    protected function buildMonthlyCounts(string $year, int $jenisDokumenId): array
    {
        $months = Kerjasama::query()
            ->where('tahun', $year)
            ->where('jenis_dokumen_id', $jenisDokumenId)
            ->pluck('tanggal_awal')
            ->filter(fn ($date) => $date !== null)
            ->map(fn ($date) => (int) $date->format('m'))
            ->values()
            ->all();

        $monthlyCounts = array_fill(1, 12, 0);

        foreach ($months as $month) {
            if (isset($monthlyCounts[$month])) {
                $monthlyCounts[$month]++;
            }
        }

        return array_values($monthlyCounts);
    }

    protected function getFilters(): ?array
    {
        $years = Kerjasama::query()
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun')
            ->pluck('tahun')
            ->map(fn ($year) => (string) $year)
            ->all();

        if (empty($years)) {
            $years = [(string) now()->year];
        }

        return array_combine($years, $years);
    }

    protected function getDefaultYear(): string
    {
        $years = $this->getFilters();

        if ($years && in_array((string) now()->year, array_keys($years), true)) {
            return (string) now()->year;
        }

        return $years ? (string) array_key_first($years) : (string) now()->year;
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
