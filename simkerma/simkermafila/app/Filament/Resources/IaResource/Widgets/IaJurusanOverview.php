<?php

namespace App\Filament\Resources\IaResource\Widgets;

use App\Models\Kerjasama;
use App\Models\MasterJurusan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class IaJurusanOverview extends BaseWidget
{
    protected string $view = 'filament.resources.ia.widgets.jurusan-overview';

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
            ->where('jenis_dokumen_id', 4)
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
        // Base Query: Dokumen IA (jenis_dokumen_id = 4), abaikan status Ditolak
        $baseQuery = Kerjasama::query()
            ->where('jenis_dokumen_id', 4)
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
            ->get(['id', 'jenis', 'tanggal_akhir']);

        $totalAll = $documents->count();
        $totalDnAll = $documents->where('jenis', 'Dalam Negeri')->count();
        $totalLnAll = $documents->where('jenis', 'Luar Negeri')->count();

        $stats = [];

        // 1. Kartu Ringkasan Total Semua Jurusan
        $statusSuffix = match ($selectedStatus) {
            'aktif' => ' (Aktif)',
            'berakhir' => ' (Berakhir)',
            default => '',
        };

        $totalTitle = $selectedYear === 'all'
            ? "Total Dokumen IA{$statusSuffix}"
            : "Total Dokumen IA ({$selectedYear}){$statusSuffix}";

        $stats[] = Stat::make(
            $totalTitle,
            new HtmlString(number_format($totalAll) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Dokumen</span>')
        )
            ->description("{$totalDnAll} Dalam Negeri · {$totalLnAll} Luar Negeri")
            ->descriptionIcon('heroicon-m-document-text')
            ->color($totalAll > 0 ? 'primary' : 'gray');

        // 2. Daftar Seluruh Master Jurusan
        $jurusans = MasterJurusan::orderBy('nama_jurusan')->get();

        $jurusanStats = [];
        foreach ($jurusans as $j) {
            $jurusanStats[$j->id] = [
                'total' => 0,
                'dn' => 0,
                'ln' => 0,
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
                    if ($doc->jenis === 'Dalam Negeri') {
                        $jurusanStats[$jid]['dn']++;
                    } elseif ($doc->jenis === 'Luar Negeri') {
                        $jurusanStats[$jid]['ln']++;
                    }
                }
            }
        }

        // 3. Buat Kartu untuk Setiap Jurusan
        foreach ($jurusans as $jurusan) {
            $data = $jurusanStats[$jurusan->id] ?? ['total' => 0, 'dn' => 0, 'ln' => 0];
            $countTotal = $data['total'];
            $countDn = $data['dn'];
            $countLn = $data['ln'];

            $displayName = preg_replace('/^Jurusan\s+/i', '', $jurusan->nama_jurusan);

            $desc = $countTotal > 0
                ? "{$countDn} Dalam Negeri · {$countLn} Luar Negeri"
                : 'Belum ada dokumen IA';

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
