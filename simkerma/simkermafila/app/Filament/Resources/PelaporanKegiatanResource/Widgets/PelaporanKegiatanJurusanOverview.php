<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Widgets;

use App\Filament\Resources\PelaporanKegiatanResource;
use App\Models\Kerjasama;
use App\Models\MasterJurusan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PelaporanKegiatanJurusanOverview extends BaseWidget
{
    protected string $view = 'filament.resources.pelaporan-kegiatan.widgets.jurusan-overview';

    public ?string $selectedYear = 'all';
    public ?string $sortBy = 'desc';

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

        // Sembunyikan untuk Mahasiswa
        if ($user->isMahasiswa()) {
            return false;
        }

        // Sembunyikan untuk role User Biasa / Umum
        $privilegeName = strtolower(trim($user->userPrivilege?->privilege?->nama ?? ''));
        if (in_array($privilegeName, ['user', 'user biasa', 'umum'])) {
            return false;
        }

        return true;
    }

    public function mount(): void
    {
        $this->selectedYear = 'all';
        $this->sortBy = 'desc';
    }

    public function updatedSelectedYear(): void
    {
        $this->cachedStats = null;
    }

    public function updatedSortBy(): void
    {
        $this->cachedStats = null;
    }

    public function setYear(string $year): void
    {
        $this->selectedYear = $year;
        $this->cachedStats = null;
    }

    public function setSort(string $sort): void
    {
        $this->sortBy = $sort;
        $this->cachedStats = null;
    }

    public function getAvailableYears(): array
    {
        $years = Kerjasama::query()
            ->whereIn('jenis_dokumen_id', [8, 9])
            ->whereNotNull('parent_id')
            ->whereHas('parent')
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
        // 1. Base Query: Dokumen Pelaporan Kegiatan yang memiliki rujukan kerjasama sah
        $baseQuery = Kerjasama::query()
            ->whereIn('jenis_dokumen_id', [8, 9])
            ->whereNotNull('parent_id')
            ->whereHas('parent')
            ->where('status_workflow', '!=', 'Ditolak')
            ->with([
                'jurusans',
                'prodis',
                'parent.jurusans',
                'parent.prodis',
                'parent.jenisDokumen',
                'parent.mitra',
                'mitra',
            ]);

        $selected = $this->selectedYear ?: 'all';

        if ($selected !== 'all') {
            $baseQuery->where(function ($q) use ($selected) {
                $q->where('tahun', $selected)
                  ->orWhere(function ($sub) use ($selected) {
                      $sub->whereNull('tahun')->whereYear('tanggal_awal', $selected);
                  })
                  ->orWhere(function ($sub) use ($selected) {
                      $sub->whereNull('tahun')->whereNull('tanggal_awal')->whereYear('created_at', $selected);
                  });
            });
        }

        $documents = $baseQuery->get(['id', 'jenis_dokumen_id', 'parent_id', 'mitra_id', 'tahun', 'tanggal_awal', 'created_at']);

        // 2. Hitung Total Rujukan Unik Mitra Institusi (Sama Persis dengan UserCountWidget di Admin)
        $totalUniqueAll = $documents
            ->groupBy(function ($cs) {
                if (!empty($cs->tahun)) return (string) $cs->tahun;
                if (!empty($cs->tanggal_awal)) return date('Y', strtotime($cs->tanggal_awal));
                return date('Y', strtotime($cs->created_at));
            })
            ->map(function ($itemsInYear) {
                return $itemsInYear->map(function ($item) {
                    $mId = $item->parent?->mitra_id ?? $item->mitra_id;
                    return !empty($mId) ? 'MITRA_' . $mId : 'PARENT_ID_' . $item->parent_id;
                })->unique()->count();
            })
            ->sum();

        $totalCsAll = $documents->where('jenis_dokumen_id', 8)->count();
        $totalPrAll = $documents->where('jenis_dokumen_id', 9)->count();

        $stats = [];

        // 3. Kartu Ringkasan Total Semua Jurusan (Total Dokumen Kerma berbasis Mitra)
        $totalTitle = $selected === 'all'
            ? 'Total Dokumen Kerma (Semua Tahun)'
            : "Total Dokumen Kerma ({$selected})";

        $stats[] = Stat::make(
            $totalTitle,
            new HtmlString(number_format($totalUniqueAll) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Mitra</span>')
        )
            ->description("Didukung {$totalCsAll} Case Study · {$totalPrAll} Press Release")
            ->descriptionIcon('heroicon-m-building-office-2')
            ->color($totalUniqueAll > 0 ? 'primary' : 'gray');

        // 4. Daftar Seluruh Master Jurusan
        $jurusans = MasterJurusan::orderBy('nama_jurusan')->get();

        $jurusanUniqueParents = [];
        $jurusanCsCount = [];
        $jurusanPrCount = [];

        foreach ($jurusans as $j) {
            $jurusanUniqueParents[$j->id] = [];
            $jurusanCsCount[$j->id] = 0;
            $jurusanPrCount[$j->id] = 0;
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

            // Fallback: Jika pelaporan tidak memiliki jurusan/prodi langsung, ambil dari parent
            if ($matchedJurusanIds->isEmpty() && $doc->parent) {
                foreach ($doc->parent->jurusans as $j) {
                    $matchedJurusanIds->push($j->id);
                }
                foreach ($doc->parent->prodis as $p) {
                    if ($p->jurusan_id) {
                        $matchedJurusanIds->push($p->jurusan_id);
                    }
                }
            }

            $mId = $doc->parent?->mitra_id ?? $doc->mitra_id;
            $uniqueKey = !empty($mId) ? 'MITRA_' . $mId : 'PARENT_ID_' . $doc->parent_id;

            foreach ($matchedJurusanIds->unique() as $jid) {
                if (isset($jurusanUniqueParents[$jid])) {
                    $jurusanUniqueParents[$jid][$uniqueKey] = true;
                    if ((int) $doc->jenis_dokumen_id === 8) {
                        $jurusanCsCount[$jid]++;
                    } elseif ((int) $doc->jenis_dokumen_id === 9) {
                        $jurusanPrCount[$jid]++;
                    }
                }
            }
        }

        // 5. Susun dan Urutkan Data Jurusan (SEMUA Jurusan Tampil, Termasuk yang 0)
        $jurusanItems = [];
        foreach ($jurusans as $jurusan) {
            $uniqueCount = count($jurusanUniqueParents[$jurusan->id] ?? []);
            $jurusanItems[] = [
                'jurusan' => $jurusan,
                'nama_jurusan' => $jurusan->nama_jurusan,
                'total' => $uniqueCount,
                'cs' => $jurusanCsCount[$jurusan->id] ?? 0,
                'pr' => $jurusanPrCount[$jurusan->id] ?? 0,
            ];
        }

        $sortBy = $this->sortBy ?: 'desc';
        usort($jurusanItems, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'desc') {
                if ($b['total'] === $a['total']) {
                    return strcmp($a['nama_jurusan'], $b['nama_jurusan']);
                }
                return $b['total'] <=> $a['total'];
            } elseif ($sortBy === 'asc') {
                if ($a['total'] === $b['total']) {
                    return strcmp($a['nama_jurusan'], $b['nama_jurusan']);
                }
                return $a['total'] <=> $b['total'];
            }
            return strcmp($a['nama_jurusan'], $b['nama_jurusan']);
        });

        // 6. Buat Kartu untuk Setiap Jurusan (SEMUA Jurusan Tampil, Termasuk yang 0)
        foreach ($jurusanItems as $index => $item) {
            $rank = $index + 1;
            $countTotal = $item['total'];
            $countCs = $item['cs'];
            $countPr = $item['pr'];

            $displayName = '#' . $rank . ' ' . preg_replace('/^Jurusan\s+/i', '', $item['nama_jurusan']);

            $desc = $countTotal > 0
                ? "{$countCs} Case Study · {$countPr} Press Release"
                : 'Belum ada kegiatan';

            $stats[] = Stat::make(
                $displayName,
                new HtmlString(number_format($countTotal) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Mitra</span>')
            )
                ->description($desc)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($countTotal > 0 ? 'success' : 'gray');
        }

        return $stats;
    }
}
