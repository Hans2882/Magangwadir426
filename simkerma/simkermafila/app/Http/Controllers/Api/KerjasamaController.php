<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kerjasama;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KerjasamaController extends Controller
{
    public function mou(Request $request): JsonResponse
    {
        return $this->index($request, 'mou');
    }

    public function moa(Request $request): JsonResponse
    {
        return $this->index($request, 'moa');
    }

    public function ia(Request $request): JsonResponse
    {
        return $this->index($request, 'ia');
    }

    public function pks(Request $request): JsonResponse
    {
        return $this->index($request, 'pks');
    }

    private function index(Request $request, string $type): JsonResponse
    {
        $types = [
            'mou' => [1],
            'moa' => [2],
            'ia' => [4],
            'pks' => [3, 5],
        ];

        $query = Kerjasama::query()
            ->with([
                'mitra:id,nama_mitra,kategori_id,negara_id,telepon,email,alamat,provinsi_id,kota_id,pic',
                'mitra.negara:id,nama_negara',
                'mitra.kategori:id,kategori',
                'mitra.provinsiModel:id,nama_provinsi',
                'mitra.kotaModel:id,nama_kota',
                'jenisDokumen:id,nama',
                'bidang:id,bidang_kerjasama',
                'provinsi:id,nama_provinsi',
                'kota:id,nama_kota',
                'parent:id,jenis_dokumen_id,judul',
                'prodis:id,nama_prodi',
            ])
            ->where('status_workflow', 'Selesai')
            ->whereIn('jenis_dokumen_id', $types[$type]);

        $this->applyFilters($query, $request);

        $documents = $query
            ->orderByDesc('tanggal_awal')
            ->orderBy('judul')
            ->get()
            ->map(fn (Kerjasama $document): array => $this->transform($document));

        return response()->json([
            'success' => true,
            'message' => 'Data ' . strtoupper($type) . ' berhasil diambil.',
            'filters' => [
                'jenis' => $type,
                'mitra_id' => $request->query('mitra_id'),
                'negara_id' => $request->query('negara_id'),
                'bidang_id' => $request->query('bidang_id'),
                'tahun' => $request->query('tahun'),
                'status' => $request->query('status', $request->query('status_kerjasama')),
                'prodi_id' => $request->query('prodi_id'),
            ],
            'total' => $documents->count(),
            'data' => $documents,
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        foreach (['mitra_id', 'bidang_id', 'tahun'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        if ($request->filled('jenis_dokumen_id')) {
            $documentTypes = $request->query('jenis_dokumen_id');
            $documentTypes = is_array($documentTypes)
                ? $documentTypes
                : [$documentTypes];

            $query->whereIn('jenis_dokumen_id', $documentTypes);
        }

        if ($request->filled('negara_id')) {
            $query->whereHas('mitra', function (Builder $mitraQuery) use ($request) {
                $mitraQuery->where('negara_id', $request->query('negara_id'));
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        $status = $request->query('status', $request->query('status_kerjasama'));

        match ($status) {
            'active' => $query->where(function (Builder $statusQuery) {
                $statusQuery->whereNull('tanggal_akhir')
                    ->orWhereDate('tanggal_akhir', '>', now()->addMonth());
            }),
            'expiring' => $query->whereNotNull('tanggal_akhir')
                ->whereDate('tanggal_akhir', '>=', today())
                ->whereDate('tanggal_akhir', '<=', now()->addMonth()),
            'expired' => $query->whereNotNull('tanggal_akhir')
                ->whereDate('tanggal_akhir', '<', today()),
            default => null,
        };

        if ($request->filled('prodi_id')) {
            $query->whereHas('prodis', function (Builder $prodiQuery) use ($request) {
                $prodiQuery->whereKey($request->query('prodi_id'));
            });
        }
    }

    private function transform(Kerjasama $document): array
    {
        return [
            'id' => $document->id,
            'jenis_dokumen' => $document->jenisDokumen?->nama,
            'jenis' => $document->jenis,
            'judul' => $document->judul,
            'nomor_dokumen' => $document->nomor_dokumen,
            'tahun' => $document->tahun,
            'tanggal_awal' => $document->tanggal_awal?->toDateString(),
            'tanggal_akhir' => $document->tanggal_akhir?->toDateString(),
            'status' => $document->status,
            'mitra' => $document->mitra ? [
                'id' => $document->mitra->id,
                'nama_mitra' => $document->mitra->nama_mitra,
                'kategori' => $document->mitra->kategori?->kategori,
                'negara' => $document->mitra->negara?->nama_negara ?? 'Indonesia',
                'telepon' => $document->mitra->telepon,
                'email' => $document->mitra->email,
                'alamat' => $document->mitra->alamat,
                'provinsi' => $document->mitra->provinsiModel?->nama_provinsi,
                'kota' => $document->mitra->kotaModel?->nama_kota,
                'pic' => $document->mitra->pic,
            ] : null,
            'provinsi' => $document->provinsi?->nama_provinsi,
            'kota' => $document->kota?->nama_kota,
            'bidang' => $document->bidang?->bidang_kerjasama,
            'program_studi' => $document->prodis->pluck('nama_prodi')->values(),
            'dokumen' => $document->link_dokumen,
            'link_perbaikan' => $document->link_perbaikan,
            'bukti_kegiatan' => $document->bukti_kegiatan,
            'link_laporan_kegiatan' => $document->link_laporan_kegiatan,
            'parent' => $document->parent ? [
                'id' => $document->parent->id,
                'jenis_dokumen_id' => $document->parent->jenis_dokumen_id,
                'judul' => $document->parent->judul,
            ] : null,
        ];
    }
}
