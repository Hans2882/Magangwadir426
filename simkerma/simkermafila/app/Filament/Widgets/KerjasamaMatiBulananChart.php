<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class KerjasamaMatiBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Kerjasama Mati per Bulan';

    protected static ?string $description = 'MoU, PKS, dan IA yang berakhir per bulan (termasuk proyeksi 1 tahun ke depan untuk MoU)';

    protected static ?int $sort = 6;

    protected static ?string $maxHeight = '340px';

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
        $range = $this->resolveFilterRange($this->filter ?? now()->year);
        $startYear = $range[0];
        $endYear = $range[1];

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

    protected function resolveFilterRange(string $filter): array
    {
        if (str_contains($filter, '-')) {
            $parts = array_map('trim', explode('-', $filter));
            $startYear = (int) ($parts[0] ?? now()->year);
            $endYear = (int) ($parts[1] ?? $startYear);

            return [$startYear, $endYear > $startYear ? $endYear : $startYear];
        }

        $year = (int) $filter;

        return [$year, $year];
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

        $options = [];
        foreach ($years as $year) {
            $options[$year] = $year;
        }

        $options[(string) now()->year . '-' . (string) now()->year] = 'Tahun ini';
        $options[(string) (now()->year - 1) . '-' . (string) now()->year] = '1 tahun terakhir';
        $options[(string) (now()->year - 5) . '-' . (string) now()->year] = '5 tahun terakhir';

        return $options;
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
