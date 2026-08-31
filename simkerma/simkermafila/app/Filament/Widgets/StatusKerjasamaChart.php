<?php

namespace App\Filament\Widgets;

use App\Models\Kerjasama;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class StatusKerjasamaChart extends ChartWidget
{
    protected ?string $heading = 'Status Kerjasama';

    protected static ?int $sort = 3;

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
    //
    // PENTING: Status Kerjasama TIDAK menghitung IA (jenis_dokumen_id = 4)
    //          sama sekali. Hanya MoU (1) dan PKS (3).
    //

    protected function getData(): array
    {
        [$startYear, $endYear] = $this->resolveYearRange();

        // Base query: hanya MoU dan PKS, TIDAK termasuk IA
        $baseQuery = Kerjasama::query()
            ->whereIn('jenis_dokumen_id', [1, 3]);

        if ($startYear !== null && $endYear !== null) {
            $baseQuery->whereBetween('tahun', [$startYear, $endYear]);
        }

        $expiringThreshold = now()->addMonths(4);

        // Aktif: tanggal_akhir > 4 bulan dari sekarang
        $aktif = (clone $baseQuery)
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '>', $expiringThreshold)
            ->count();

        // Akan Berakhir: tanggal_akhir dalam 4 bulan ke depan
        $akanHabis = (clone $baseQuery)
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '>=', now())
            ->whereDate('tanggal_akhir', '<=', $expiringThreshold)
            ->count();

        // Berakhir: tanggal_akhir < hari ini
        $habis = (clone $baseQuery)
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '<', now())
            ->count();

        $total = $aktif + $akanHabis + $habis ?: 1;

        $labels = [
            'Aktif: '      . number_format($aktif      / $total * 100, 1) . '% (' . $aktif . ')',
            'Akan Berakhir: ' . number_format($akanHabis / $total * 100, 1) . '% (' . $akanHabis . ')',
            'Berakhir: '      . number_format($habis      / $total * 100, 1) . '% (' . $habis . ')',
        ];

        $details = [
            [
                'label' => 'Aktif',
                'count' => $aktif,
                'prodi' => $this->buildProdiDetail($baseQuery, 'active'),
            ],
            [
                'label' => 'Akan Berakhir',
                'count' => $akanHabis,
                'prodi' => $this->buildProdiDetail($baseQuery, 'expiring'),
            ],
            [
                'label' => 'Berakhir',
                'count' => $habis,
                'prodi' => $this->buildProdiDetail($baseQuery, 'expired'),
            ],
        ];

        return [
            'datasets' => [
                [
                    'data'            => [$aktif, $akanHabis, $habis],
                    'backgroundColor' => ['#27ae60', '#f39c12', '#c0392b'],
                    'borderColor'     => '#ffffff',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $labels,
            'extra'  => ['details' => $details],
        ];
    }

    // ─── Prodi detail builder ─────────────────────────────────────────
    //
    // Status-based: active / expired / other
    // Base query sudah dibatasi ke jenis_dokumen_id IN (1,3) — tanpa IA.
    //

    protected function buildProdiDetail($query, string $status): array
    {
        $q = (clone $query)->with('prodis');

        switch ($status) {
            case 'active':
                $q->whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '>', now()->addMonths(4));
                break;
            case 'expiring':
                $q->whereNotNull('tanggal_akhir')
                    ->whereDate('tanggal_akhir', '>=', now())
                    ->whereDate('tanggal_akhir', '<=', now()->addMonths(4));
                break;
            case 'expired':
                $q->whereNotNull('tanggal_akhir')->whereDate('tanggal_akhir', '<', now());
                break;
        }

        $records = $q->get();

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
                'tooltip' => [
                    'titleFont' => ['size' => 16, 'weight' => 'bold'],
                    'bodyFont' => ['size' => 16],
                    'padding' => 12,
                ],
            ],
            'animation' => [
                'animateScale' => true,
                'animateRotate' => true,
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }
}
