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
            'month' => ['sometimes', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $monthStr = $validated['month'] ?? now()->format('Y-m');
        $month    = CarbonImmutable::createFromFormat('Y-m-d', $monthStr . '-01');

        if (! $month) {
            return response()->json([
                'events' => [],
                'error'  => 'Format bulan tidak valid.',
            ], 422);
        }

        $startDate = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $endDate   = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $events = Kerjasama::query()
            ->with('mitra:id,nama_mitra')
            /*
             * Filter "kerjasama yang akan berakhir":
             *  - status_workflow = 'Selesai'  → hanya yang sudah disetujui
             *  - tanggal_akhir  >= hari ini   → belum kedaluwarsa
             *  - tanggal_akhir  dalam rentang bulan yang tampil (termasuk minggu leading/trailing)
             *
             * Kalau data tidak muncul, cek dulu nilai `status_workflow` di database
             * (mungkin nilainya 'Aktif', 'Disetujui', atau label lain).
             * Kalau ingin tampil tanpa peduli status, hapus baris where('status_workflow', ...).
             */
            ->where('status_workflow', 'Selesai')
            ->whereNotNull('tanggal_akhir')
            ->whereDate('tanggal_akhir', '>=', today())
            ->whereBetween('tanggal_akhir', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->orderBy('tanggal_akhir')
            ->orderBy('judul')
            ->get(['id', 'judul', 'mitra_id', 'tanggal_akhir'])
            ->map(fn (Kerjasama $kerjasama): array => [
                'id'      => $kerjasama->id,
                'title'   => $kerjasama->judul,
                'partner' => $kerjasama->mitra?->nama_mitra,
                'date'    => $kerjasama->tanggal_akhir->toDateString(),
            ]);

        return response()->json(['events' => $events]);
    }
}