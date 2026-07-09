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
        $year = $this->filter ?? now()->year;

        $datasets = [];
        $documentTypes = [
            1 => ['label' => 'MoU aktif', 'color' => '#0f766e'],
            3 => ['label' => 'PKS aktif', 'color' => '#2563eb'],
            4 => ['label' => 'IA aktif', 'color' => '#f59e0b'],
        ];

        foreach ($documentTypes as $documentTypeId => $config) {
            $monthlyGrowth = [];

            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $activeCount = Kerjasama::query()
                    ->where('jenis_dokumen_id', $documentTypeId)
                    ->whereNotNull('tanggal_akhir')
                    ->whereDate('tanggal_akhir', '>=', $startOfMonth)
                    ->whereDate('tanggal_akhir', '<=', $endOfMonth)
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
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
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
