<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;

class StatusKerjasamaChart extends ChartWidget
{
    protected static ?string $heading = 'Status Kerjasama';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

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
        $range = $this->resolveFilterRange($this->filter ?? $this->getDefaultYear());
        $startYear = $range[0];
        $endYear = $range[1];

        $baseQuery = Kerjasama::query();

        if ($startYear !== null && $endYear !== null) {
            $baseQuery->whereBetween('tahun', [$startYear, $endYear]);
        }

        $aktif = (clone $baseQuery)
            ->whereIn('jenis_dokumen_id', [1, 3])
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '>=', now())
            ->count();

        $habis = (clone $baseQuery)
            ->whereIn('jenis_dokumen_id', [1, 3])
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '<', now())
            ->count();

        $lainnya = (clone $baseQuery)
            ->whereIn('jenis_dokumen_id', [1, 3])
            ->whereNull('tanggal_akhir')
            ->count();

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

    protected function getDefaultYear(): string
    {
        return (string) now()->year;
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
