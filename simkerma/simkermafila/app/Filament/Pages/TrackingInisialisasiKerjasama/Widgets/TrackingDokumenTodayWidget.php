<?php

namespace App\Filament\Pages\TrackingInisialisasiKerjasama\Widgets;

use App\Models\Kerjasama;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class TrackingDokumenTodayWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int | array | null $columns = [
        'default' => 1,
        'sm' => 2,
        'md' => 3,
    ];

    protected function getStats(): array
    {
        $today = Carbon::today();

        $docsToday = Kerjasama::query()
            ->with('jenisDokumen')
            ->whereDate('created_at', $today)
            ->get(['id', 'jenis_dokumen_id', 'created_at']);

        $totalToday = $docsToday->count();

        // Rincian per jenis dokumen
        $breakdownParts = [];
        $grouped = $docsToday->groupBy('jenis_dokumen_id');

        // Prioritas urutan tampilan: MoU, PKS, IA, MoA, SPK, LoC, Case Study, Press Release
        $priorityOrder = [
            1 => 'MoU',
            3 => 'PKS',
            4 => 'IA',
            2 => 'MoA',
            5 => 'SPK',
            6 => 'LoC',
            8 => 'Case Study',
            9 => 'Press Release',
        ];

        foreach ($priorityOrder as $id => $defaultName) {
            if ($grouped->has($id)) {
                $count = $grouped->get($id)->count();
                $name = $grouped->get($id)->first()->jenisDokumen?->nama ?? $defaultName;
                $breakdownParts[] = "{$count} {$name}";
            }
        }

        foreach ($grouped as $id => $items) {
            if (!array_key_exists($id, $priorityOrder)) {
                $name = $items->first()->jenisDokumen?->nama ?? 'Lainnya';
                $breakdownParts[] = "{$items->count()} {$name}";
            }
        }

        $breakdownText = !empty($breakdownParts)
            ? implode(' · ', $breakdownParts)
            : 'Belum ada dokumen diinput hari ini';

        $dateFormatted = Carbon::now()->locale('id')->translatedFormat('d M Y');

        return [
            Stat::make(
                "Dokumen Diinput Hari Ini ({$dateFormatted})",
                new HtmlString(number_format($totalToday) . ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; font-weight: normal;">Dokumen</span>')
            )
                ->description($breakdownText)
                ->descriptionIcon('heroicon-m-document-text')
                ->color($totalToday > 0 ? 'primary' : 'gray'),
        ];
    }
}
