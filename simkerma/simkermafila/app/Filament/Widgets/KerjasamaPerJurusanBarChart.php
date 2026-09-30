<?php

namespace App\Filament\Widgets;

use App\Models\MasterJurusan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class KerjasamaPerJurusanBarChart extends ChartWidget
{
    protected ?string $heading = 'Jumlah PKS dan IA per Jurusan';

    protected ?string $description = 'Jumlah dokumen berdasarkan rentang tahun terpilih';

    protected static ?int $sort = 8;

    protected ?string $maxHeight = '380px';

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.pie-chart-with-details';

    public ?string $filter = 'this_year';
    public ?string $customStartYear = null;
    public ?string $customEndYear = null;
    public ?string $jurusanId = null;
    public ?string $prodiId = null;

    public function mount(): void
    {
        $this->filter ??= 'this_year';
        $this->customStartYear ??= (string) now()->year;
        $this->customEndYear ??= (string) now()->year;

        parent::mount();
    }

    #[On('filter-updated')]
    public function applyGlobalFilter($preset, $startYear = null, $endYear = null, $jurusanId = null, $prodiId = null): void
    {
        $this->filter = $preset;
        $this->customStartYear = $startYear;
        $this->customEndYear = $endYear;
        $this->jurusanId = $jurusanId;
        $this->prodiId = $prodiId;
    }

    public function getChartFilters(): array
    {
        return [
            'this_year' => 'Tahun Ini (' . now()->year . ')',
            'last_1_year' => '1 Tahun Terakhir',
            'last_5_years' => '5 Tahun Terakhir',
            'last_10_years' => '10 Tahun Terakhir',
            'all_time' => 'Semua Waktu',
            'custom' => 'Tahun Kustom (Pilih Manual)',
        ];
    }

    public function getChartDetails(): array
    {
        return [];
    }

    protected function getData(): array
    {
        [$startYear, $endYear] = $this->resolveYearRange();

        $counts = DB::table('kerjasama_jurusan as kj')
            ->join('kerjasama as k', 'k.id', '=', 'kj.kerjasama_id')
            ->select('kj.jurusan_id')
            ->selectRaw('COUNT(DISTINCT CASE WHEN k.jenis_dokumen_id = 3 THEN k.id END) as pks_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN k.jenis_dokumen_id = 4 THEN k.id END) as ia_count')
            ->when($startYear !== null, fn ($query) => $query->whereBetween('k.tahun', [$startYear, $endYear]))
            ->when($this->jurusanId, fn ($query) => $query->where('kj.jurusan_id', $this->jurusanId))
            ->when($this->prodiId, fn ($query) => $query->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('kerjasama_prodi as kp')
                    ->whereColumn('kp.kerjasama_id', 'k.id')
                    ->where('kp.prodi_id', $this->prodiId);
            }))
            ->groupBy('kj.jurusan_id')
            ->get()
            ->keyBy('jurusan_id');

        $jurusans = MasterJurusan::query()
            ->when($this->jurusanId, fn ($query) => $query->whereKey($this->jurusanId))
            ->orderBy('nama_jurusan')
            ->get(['id', 'nama_jurusan']);

        return [
            'type' => 'bar',
            'labels' => $jurusans->pluck('nama_jurusan')->all(),
            'datasets' => [
                [
                    'label' => 'PKS',
                    'data' => $jurusans->map(fn (MasterJurusan $jurusan): int => (int) ($counts->get($jurusan->id)->pks_count ?? 0))->all(),
                    'backgroundColor' => '#0f766e',
                    'borderColor' => '#0f766e',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'IA',
                    'data' => $jurusans->map(fn (MasterJurusan $jurusan): int => (int) ($counts->get($jurusan->id)->ia_count ?? 0))->all(),
                    'backgroundColor' => '#d97706',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function resolveYearRange(): array
    {
        $currentYear = now()->year;

        [$startYear, $endYear] = match ($this->filter ?? 'this_year') {
            'last_1_year' => [$currentYear - 1, $currentYear],
            'last_5_years' => [$currentYear - 5, $currentYear],
            'last_10_years' => [$currentYear - 10, $currentYear],
            'all_time' => [null, null],
            'custom' => [(int) ($this->customStartYear ?? $currentYear), (int) ($this->customEndYear ?? $currentYear)],
            default => [$currentYear, $currentYear],
        };

        return $startYear !== null && $startYear > $endYear
            ? [$endYear, $startYear]
            : [$startYear, $endYear];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'stacked' => false,
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}