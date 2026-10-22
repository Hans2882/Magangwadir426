<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\Kerjasama;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserCountWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.user-count-widget';

    public ?string $selectedYear = null;

    public function mount(): void
    {
        $this->selectedYear = (string) date('Y');
    }

    public function updatedSelectedYear(): void
    {
        $this->cachedStats = null;
    }

    public function setYear(string $year): void
    {
        $this->selectedYear = $year;
        $this->cachedStats = null;
    }

    public function getAvailableYears(): array
    {
        $years = Kerjasama::query()
            ->where('jenis_dokumen_id', 8)
            ->whereNotNull('parent_id')
            ->whereHas('parent')
            ->where('status_workflow', '!=', 'Ditolak')
            ->get()
            ->map(function ($cs) {
                if (!empty($cs->tahun)) {
                    return (string) $cs->tahun;
                }
                if (!empty($cs->tanggal_awal)) {
                    return date('Y', strtotime($cs->tanggal_awal));
                }
                return date('Y', strtotime($cs->created_at));
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

        // Tambahkan tahun lalu (currentYear - 1) jika belum ada agar langsung tersedia di pilihan
        $lastYear = (string) ((int) $currentYear - 1);
        if (!in_array($lastYear, $years)) {
            $years[] = $lastYear;
            rsort($years);
        }

        $options = [];
        foreach ($years as $y) {
            $options[$y] = $y === $currentYear ? "{$y} (Tahun Ini)" : "Tahun {$y}";
        }
        $options['all'] = 'Semua Tahun (Akumulasi)';

        return $options;
    }

    protected function getStats(): array
    {
        // 1. Total Pengguna Sistem
        $stats = [
            Stat::make('Total Pengguna', User::count())
                ->description('Semua pengguna terdaftar di sistem')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];

        // 2. Query Case Study ber-MOU/PKS/IA
        $query = Kerjasama::query()
            ->where('jenis_dokumen_id', 8)
            ->whereNotNull('parent_id')
            ->whereHas('parent')
            ->where('status_workflow', '!=', 'Ditolak')
            ->with(['parent.jenisDokumen']);

        $selected = $this->selectedYear ?: (string) date('Y');

        if ($selected !== 'all') {
            $query->where(function ($q) use ($selected) {
                $q->where('tahun', $selected)
                  ->orWhere(function ($sub) use ($selected) {
                      $sub->whereNull('tahun')
                          ->whereYear('tanggal_awal', $selected);
                  })
                  ->orWhere(function ($sub) use ($selected) {
                      $sub->whereNull('tahun')
                          ->whereNull('tanggal_awal')
                          ->whereYear('created_at', $selected);
                  });
            });
        }

        $caseStudies = $query->get();

        // Deduplikasi: nomor dokumen MoU/PKS/IA yang sama di tahun yang sama dihitung 1
        $uniqueCount = $caseStudies
            ->groupBy(function ($cs) {
                if (!empty($cs->tahun)) {
                    return (string) $cs->tahun;
                }
                if (!empty($cs->tanggal_awal)) {
                    return date('Y', strtotime($cs->tanggal_awal));
                }
                return date('Y', strtotime($cs->created_at));
            })
            ->map(function ($itemsInYear) {
                return $itemsInYear->map(function ($item) {
                    $nomor = trim($item->parent?->nomor_dokumen ?? '');
                    return !empty($nomor) ? strtolower($nomor) : 'PARENT_ID_' . $item->parent_id;
                })->unique()->count();
            })
            ->sum();

        // Label & Deskripsi
        if ($selected === 'all') {
            $labelTitle = 'Dokumen Kerma (Semua Tahun)';
            $desc = 'Total rujukan unik MoU/PKS/IA dari seluruh Case Study';
        } else {
            $labelTitle = "Dokumen Kerma ({$selected})";
            $desc = "Rujukan unik MoU/PKS/IA dari Case Study tahun {$selected}";
        }

        $stats[] = Stat::make($labelTitle, number_format($uniqueCount))
            ->description($desc)
            ->descriptionIcon('heroicon-m-document-check')
            ->color($uniqueCount > 0 ? 'success' : 'gray');

        // 3. Stat Total Akumulasi Dokumen Kerma (jika sedang memilih tahun tertentu)
        if ($selected !== 'all') {
            $allCaseStudies = Kerjasama::query()
                ->where('jenis_dokumen_id', 8)
                ->whereNotNull('parent_id')
                ->whereHas('parent')
                ->where('status_workflow', '!=', 'Ditolak')
                ->with(['parent.jenisDokumen'])
                ->get();

            $totalAllYears = $allCaseStudies
                ->groupBy(function ($cs) {
                    if (!empty($cs->tahun)) {
                        return (string) $cs->tahun;
                    }
                    if (!empty($cs->tanggal_awal)) {
                        return date('Y', strtotime($cs->tanggal_awal));
                    }
                    return date('Y', strtotime($cs->created_at));
                })
                ->map(function ($itemsInYear) {
                    return $itemsInYear->map(function ($item) {
                        $nomor = trim($item->parent?->nomor_dokumen ?? '');
                        return !empty($nomor) ? strtolower($nomor) : 'PARENT_ID_' . $item->parent_id;
                    })->unique()->count();
                })
                ->sum();

            $stats[] = Stat::make('Total Dokumen Kerma', number_format($totalAllYears))
                ->description('Akumulasi seluruh tahun')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('warning');
        }

        return $stats;
    }
}
