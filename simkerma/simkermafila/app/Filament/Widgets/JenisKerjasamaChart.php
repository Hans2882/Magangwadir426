<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class JenisKerjasamaChart extends ChartWidget
{
    protected ?string $heading = 'Jenis Kerjasama';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected string $view = 'filament.widgets.pie-chart-with-details';

    /**
     * Filter preset: this_year | last_1_year | last_5_years | last_10_years | all_time | custom
     */
    public ?string $filter = 'this_year';

    /** Custom year range (only used when filter = 'custom') */
    public ?string $customStartYear = null;
    public ?string $customEndYear = null;

    public function mount(): void
    {
        $this->filter ??= 'this_year';
        $this->customStartYear ??= (string) now()->year;
        $this->customEndYear ??= (string) now()->year;

        parent::mount();
    }

    /**
     * Listen to the global DashboardFilterWidget event so global filter
     * also drives this chart.
     */
    #[On('filter-updated')]
    public function applyGlobalFilter($preset, $startYear = null, $endYear = null): void
    {
        $this->filter = $preset;
        $this->customStartYear = $startYear;
        $this->customEndYear = $endYear;
    }

    // ─── Filter dropdown options shown in the chart header ────────────

    public function getChartFilters(): array
    {
        return [
            'this_year'     => 'Tahun Ini (' . now()->year . ')',
            'last_1_year'   => '1 Tahun Terakhir',
            'last_5_years'  => '5 Tahun Terakhir',
            'last_10_years' => '10 Tahun Terakhir',
            'all_time'      => 'Semua Waktu',
            'custom'        => 'Tahun Kustom (Pilih Manual)',
        ];
    }

    // ─── Detail data exposed to blade ─────────────────────────────────

    public function getChartDetails(): array
    {
        $data = $this->getData();

        return $data['extra']['details'] ?? [];
    }

    // ─── Core data builder ────────────────────────────────────────────

    protected function getData(): array
    {
        [$startYear, $endYear] = $this->resolveYearRange();

        $query = Kerjasama::query();

        if ($startYear !== null && $endYear !== null) {
            $query->whereBetween('tahun', [$startYear, $endYear]);
        }

        $mou = (clone $query)->where('jenis_dokumen_id', 1)->count();
        $pks = (clone $query)->where('jenis_dokumen_id', 3)->count();
        $ia  = (clone $query)->where('jenis_dokumen_id', 4)->count();

        $total = $mou + $pks + $ia ?: 1;

        $documentTypes = [
            1 => ['label' => 'MoU', 'count' => $mou],
            3 => ['label' => 'PKS', 'count' => $pks],
            4 => ['label' => 'IA',  'count' => $ia],
        ];

        $labels = [];
        $datasetData = [];

        foreach ($documentTypes as $docId => $config) {
            $datasetData[] = $config['count'];
            $labels[] = $config['label'] . ': ' . number_format(($config['count'] / $total) * 100, 1) . '% (' . $config['count'] . ')';
        }

        $details = [];
        foreach ($documentTypes as $docId => $config) {
            $details[] = [
                'label' => $config['label'],
                'count' => $config['count'],
                'prodi' => $this->buildProdiDetail($query, $docId),
            ];
        }

        return [
            'datasets' => [
                [
                    'data'            => $datasetData,
                    'backgroundColor' => ['#c0392b', '#e67e22', '#f39c12'],
                    'borderColor'     => '#ffffff',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $labels,
            'extra'  => ['details' => $details],
        ];
    }

    // ─── Prodi detail builder ─────────────────────────────────────────

    protected function buildProdiDetail($query, int $documentTypeId): array
    {
        $records = (clone $query)
            ->where('jenis_dokumen_id', $documentTypeId)
            ->with('prodis')
            ->get();

        $grouped = [];
        foreach ($records as $record) {
            foreach ($record->prodis as $prodi) {
                $name = trim((string) ($prodi->nama_prodi ?? ''));
                if ($name === '') {
                    continue;
                }

                $grouped[$name] = ($grouped[$name] ?? 0) + 1;
            }
        }

        ksort($grouped);

        return array_map(function (string $name, int $count): array {
            return ['name' => $name, 'count' => $count];
        }, array_keys($grouped), array_values($grouped));
    }

    // ─── Year range resolver ──────────────────────────────────────────

    protected function resolveYearRange(): array
    {
        $preset = $this->filter ?? 'this_year';

        $start = now()->year;
        $end   = now()->year;

        switch ($preset) {
            case 'last_1_year':
                $start = now()->year - 1;
                break;
            case 'last_5_years':
                $start = now()->year - 5;
                break;
            case 'last_10_years':
                $start = now()->year - 10;
                break;
            case 'all_time':
                return [null, null]; // no filter → semua data
            case 'custom':
                $start = (int) ($this->customStartYear ?? now()->year);
                $end   = (int) ($this->customEndYear ?? now()->year);
                break;
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    // ─── Chart type & options ─────────────────────────────────────────

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
                    'labels' => ['font' => ['size' => 12], 'padding' => 15],
                ],
            ],
        ];
    }
}