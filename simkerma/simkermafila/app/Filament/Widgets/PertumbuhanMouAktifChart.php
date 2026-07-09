<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PertumbuhanMouAktifChart extends ChartWidget
{
    protected static ?string $heading = 'Pertumbuhan Kerjasama Aktif';

    protected static ?string $description = 'Jumlah MoU, PKS, dan IA aktif per bulan';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '320px';

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

        $labels = [];
        $monthPoints = [];
        $current = Carbon::create($startYear, 1, 1)->startOfMonth();
        $end = Carbon::create($endYear, 12, 1)->endOfMonth();

        while ($current->lte($end)) {
            $labels[] = $this->monthLabel($current->month) . ' ' . $current->year;
            $monthPoints[] = ['year' => $current->year, 'month' => $current->month];
            $current->addMonth();
        }

        $datasets = [];
        $documentTypes = [
            1 => ['label' => 'MoU aktif', 'color' => '#0f766e'],
            3 => ['label' => 'PKS aktif', 'color' => '#2563eb'],
            4 => ['label' => 'IA aktif', 'color' => '#f59e0b'],
        ];

        foreach ($documentTypes as $documentTypeId => $config) {
            $monthlyGrowth = [];

            foreach ($monthPoints as $point) {
                $startOfMonth = Carbon::create($point['year'], $point['month'], 1)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $activeCount = Kerjasama::query()
                    ->where('jenis_dokumen_id', $documentTypeId)
                    ->whereDate('tanggal_awal', '<=', $endOfMonth)
                    ->where(function ($query) use ($endOfMonth): void {
                        $query->whereNull('tanggal_akhir')->orWhereDate('tanggal_akhir', '>=', $endOfMonth);
                    })
                    ->count();

                $monthlyGrowth[] = $activeCount;
            }

            $datasets[] = [
                'label' => $config['label'],
                'data' => $monthlyGrowth,
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
