<?php

namespace App\Filament\Resources\PksSpkResource\Widgets;

use App\Models\Kerjasama;
use App\Models\MasterJurusan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PksJurusanOverview extends BaseWidget
{
    protected string $view = 'filament.resources.pks.widgets.jurusan-overview';

    public ?string $selectedYear = 'all';
    public ?string $selectedStatus = 'all';

    protected int | array | null $columns = [
        'default' => 1,
        'sm' => 2,
        'md' => 3,
        'xl' => 4,
    ];

    public static function canView(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isMahasiswa()) {
            return false;
        }

        $privilegeName = strtolower(trim($user->userPrivilege?->privilege?->nama ?? ''));
        if (in_array($privilegeName, ['user', 'user biasa', 'umum'])) {
            return false;
        }

        return true;
    }

    public function mount(): void
    {
        $this->selectedYear = 'all';
        $this->selectedStatus = 'all';
    }

    public function updatedSelectedYear(): void
    {
        $this->cachedStats = null;
    }

    public function updatedSelectedStatus(): void
    {
        $this->cachedStats = null;
    }

    public function setYear(string $year): void
    {
        $this->selectedYear = $year;
        $this->cachedStats = null;
    }

    public function setStatus(string $status): void
    {
        $this->selectedStatus = $status;
        $this->cachedStats = null;
    }

    public function getAvailableYears(): array
    {
        $years = Kerjasama::query()
            ->whereIn('jenis_dokumen_id', [2, 3, 5, 6])
            ->where('status_workflow', '!=', 'Ditolak')
            ->get(['tahun', 'tanggal_awal', 'created_at'])
            ->map(function ($doc) {
                if (!empty($doc->tahun)) {
                    return (string) $doc->tahun;
                }
                if (!empty($doc->tanggal_awal)) {
                    return date('Y', strtotime($doc->tanggal_awal));
                }
                return date('Y', strtotime($doc->created_at));
            })
            ->unique()
            ->filter()
            ->sortDesc()
            ->values()
            ->all();

        $currentYear = (string) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        $lastYear = (string) ((int) $currentYear - 1);
        if (!in_array($lastYear, $years)) {
            $years[] = $lastYear;
            rsort($years);
        }

        $options = [
            'all' => 'Semua Tahun (Akumulasi)',
        ];

        foreach ($years as $y) {
            $options[$y] = $y === $currentYear ? "{$y} (Tahun Ini)" : "Tahun {$y}";
        }

        return $options;
    }

    protected function getStats(): array
    {
        // 2 = MoA, 3 = PKS, 5 = SPK, 6 = LoC
        $baseQuery = Kerjasama::query()
            ->whereIn('jenis_dokumen_id', [2, 3, 5, 6])
            ->where('status_workflow', '!=', 'Ditolak');

        $selectedYear = $this->selectedYear ?: 'all';
        if ($selectedYear !== 'all') {
            $baseQuery->where(function ($q) use ($selectedYear) {
                $q->where('tahun', $selectedYear)
                  ->orWhere(function ($sub) use ($selectedYear) {
                      $sub->whereNull('tahun')->whereYear('tanggal_awal', $selectedYear);
                  })
                  ->orWhere(function ($sub) use ($selectedYear) {
                      $sub->whereNull('tahun')->whereNull('tanggal_awal')->whereYear('created_at', $selectedYear);
                  });
            });
        }

        $selectedStatus = $this->selectedStatus ?: 'all';
        if ($selectedStatus === 'aktif') {
            $baseQuery->where(function ($q) {
                $q->whereNull('tanggal_akhir')
                  ->orWhereDate('tanggal_akhir', '>=', now()->startOfDay());
            });
        } elseif ($selectedStatus === 'berakhir') {
            $baseQuery->whereNotNull('tanggal_akhir')
                      ->whereDate('tanggal_akhir', '<', now()->startOfDay());
        }

        $documents = (clone $baseQuery)
            ->with(['jurusans', 'prodis'])
            ->get(['id', 'jenis_dokumen_id', 'tanggal_akhir']);

        $totalAll = $documents->count();
        $totalPksAll = $documents->where('jenis_dokumen_id', 3)->count();
        $totalMoaAll = $documents->where('jenis_dokumen_id', 2)->count();
        $totalSpkAll = $documents->where('jenis_dokumen_id', 5)->count();
        $totalLocAll = $documents->where('jenis_dokumen_id', 6)->count();

        $stats = [];

        // 1. Kartu Ringkasan Total Semua Jurusan
        $statusSuffix = match ($selectedStatus) {
            'aktif' => ' (Aktif)',
            'berakhir' => ' (Berakhir)',
            default => '',
        };

        $totalTitle = $selectedYear === 'all'
            ? "Total PKS / SPK{$statusSuffix}"
            : "Total PKS / SPK ({$selectedYear}){$statusSuffix}";

        $totalParts = [
            "{$totalPksAll} PKS",
            "{$totalMoaAll} MoA",
            "{$totalSpkAll} SPK",
            "{$totalLocAll} LoC",
        ];
        $totalDesc = implode(' · ', $totalParts);

        $stats[] = Stat::make(
            $totalTitle,
            new HtmlString(number_format($totalAll) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Dokumen</span>')
        )
            ->description($totalDesc)
            ->descriptionIcon('heroicon-m-document-check')
            ->color($totalAll > 0 ? 'primary' : 'gray');

        // 2. Daftar Seluruh Master Jurusan
        $jurusans = MasterJurusan::orderBy('nama_jurusan')->get();

        $jurusanStats = [];
        foreach ($jurusans as $j) {
            $jurusanStats[$j->id] = [
                'total' => 0,
                'pks' => 0,
                'moa' => 0,
                'spk' => 0,
                'loc' => 0,
            ];
        }

        foreach ($documents as $doc) {
            $matchedJurusanIds = collect();

            foreach ($doc->jurusans as $j) {
                $matchedJurusanIds->push($j->id);
            }

            foreach ($doc->prodis as $p) {
                if ($p->jurusan_id) {
                    $matchedJurusanIds->push($p->jurusan_id);
                }
            }

            foreach ($matchedJurusanIds->unique() as $jid) {
                if (isset($jurusanStats[$jid])) {
                    $jurusanStats[$jid]['total']++;
                    match ((int) $doc->jenis_dokumen_id) {
                        3 => $jurusanStats[$jid]['pks']++,
                        2 => $jurusanStats[$jid]['moa']++,
                        5 => $jurusanStats[$jid]['spk']++,
                        6 => $jurusanStats[$jid]['loc']++,
                        default => null,
                    };
                }
            }
        }

        // 3. Buat Kartu untuk Setiap Jurusan
        foreach ($jurusans as $jurusan) {
            $data = $jurusanStats[$jurusan->id] ?? ['total' => 0, 'pks' => 0, 'moa' => 0, 'spk' => 0, 'loc' => 0];
            $countTotal = $data['total'];
            $countPks = $data['pks'];
            $countMoa = $data['moa'];
            $countSpk = $data['spk'];
            $countLoc = $data['loc'];

            $displayName = preg_replace('/^Jurusan\s+/i', '', $jurusan->nama_jurusan);

            $parts = [
                "{$countPks} PKS",
                "{$countMoa} MoA",
                "{$countSpk} SPK",
                "{$countLoc} LoC",
            ];

            $desc = implode(' · ', $parts);

            $stats[] = Stat::make(
                $displayName,
                new HtmlString(number_format($countTotal) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Dokumen</span>')
            )
                ->description($desc)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($countTotal > 0 ? 'success' : 'gray');
        }

        return $stats;
    }
}
