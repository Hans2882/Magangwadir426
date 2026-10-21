<?php

namespace App\Http\Controllers;

use App\Models\Kerjasama;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KerjasamaCalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month'            => ['sometimes', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'mode'             => ['sometimes', 'in:kerjasama,tracking'],
            'jenis_dokumen_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $monthStr = $validated['month'] ?? now()->format('Y-m');
        $month    = CarbonImmutable::createFromFormat('Y-m-d', $monthStr . '-01');
        $mode     = $validated['mode'] ?? 'kerjasama';
        $jenisDokumenId = ! empty($validated['jenis_dokumen_id'])
            ? (int) $validated['jenis_dokumen_id']
            : null;

        if (! $month) {
            return response()->json([
                'events' => [],
                'error'  => 'Format bulan tidak valid.',
            ], 422);
        }

        $startDate = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $endDate   = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $events = $mode === 'tracking'
            ? $this->trackingEvents($startDate, $endDate, $jenisDokumenId)
            : $this->kerjasamaEvents($startDate, $endDate, $jenisDokumenId);

        return response()->json(['events' => $events]);
    }

    /**
     * Kerjasama yang berakhir (tanggal_akhir) pada bulan yang tampil.
     * Termasuk yang sudah lewat (is_past = true).
     */
    private function kerjasamaEvents(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $jenisDokumenId
    ): array {
        return Kerjasama::query()
            ->with(['mitra:id,nama_mitra', 'jenisDokumen:id,nama'])
            ->where('status_workflow', 'Selesai')
            ->whereNotNull('tanggal_akhir')
            ->whereBetween('tanggal_akhir', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->when($jenisDokumenId, fn ($q) => $q->where('jenis_dokumen_id', $jenisDokumenId))
            ->orderBy('tanggal_akhir')
            ->orderBy('judul')
            ->get(['id', 'judul', 'mitra_id', 'jenis_dokumen_id', 'tanggal_akhir'])
            ->map(fn (Kerjasama $k): array => [
                'id'            => $k->id,
                'title'         => $k->judul,
                'partner'       => $k->mitra?->nama_mitra,
                'jenis_dokumen' => $k->jenisDokumen?->nama,
                'date'          => $k->tanggal_akhir->toDateString(),
                'is_past'       => $k->tanggal_akhir->isPast(),
            ])
            ->all();
    }

    /**
     * Tracking Dokumen Kerjasama: dokumen yang DIBUAT pada bulan yang tampil.
     * Mengikuti logika halaman TrackingInisialisasiKerjasama (created_at).
     */
    private function trackingEvents(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $jenisDokumenId
    ): array {
        return Kerjasama::query()
            ->with(['mitra:id,nama_mitra', 'jenisDokumen:id,nama'])
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->endOfDay(),
            ])
            ->when($jenisDokumenId, fn ($q) => $q->where('jenis_dokumen_id', $jenisDokumenId))
            ->orderBy('created_at')
            ->orderBy('judul')
            ->get(['id', 'judul', 'mitra_id', 'jenis_dokumen_id', 'created_at'])
            ->map(fn (Kerjasama $k): array => [
                'id'            => $k->id,
                'title'         => $k->judul,
                'partner'       => $k->mitra?->nama_mitra,
                'jenis_dokumen' => $k->jenisDokumen?->nama,
                'date'          => $k->created_at->toDateString(),
                'is_past'       => false,
            ])
            ->all();
    }
}