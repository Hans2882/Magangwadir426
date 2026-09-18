<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kerjasama;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KerjasamaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ENDPOINT
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE FILTER
    |--------------------------------------------------------------------------
    */

    private function normalizeFilter(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : [$value];

        return array_values(
            array_filter(
                $values,
                fn ($item) =>
                    $item !== null &&
                    $item !== ''
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN QUERY
    |--------------------------------------------------------------------------
    */

    private function index(
        Request $request,
        string $type
    ): JsonResponse {

        $types = [
            'mou' => [1],
            'moa' => [2],
            'ia'  => [4],
            'pks' => [3, 5],
        ];

        if (!isset($types[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis endpoint tidak valid.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL FILTER
        |--------------------------------------------------------------------------
        */

        $mitraIds = $this->normalizeFilter(
            $request->query('mitra_id')
        );

        $bidangIds = $this->normalizeFilter(
            $request->query('bidang_id')
        );

        $tahunValues = $this->normalizeFilter(
            $request->query('tahun')
        );

        $negaraIds = $this->normalizeFilter(
            $request->query('negara_id')
        );

        $jenisValues = $this->normalizeFilter(
            $request->query('jenis')
        );

        $statuses = array_map(
    function ($status) {
        return match (strtolower(trim($status))) {
            'aktif', 'active' => 'active',
            'akan berakhir', 'expiring' => 'expiring',
            'berakhir', 'expired' => 'expired',
            default => strtolower(trim($status)),
        };
    },
    $this->normalizeFilter(
        $request->query(
            'status',
            $request->query('status_kerjasama')
        )
    )
);

        $jenisDokumen = array_values(
            array_filter(
                array_map(
                    'intval',
                    $this->normalizeFilter(
                        $request->query('jenis_dokumen_id')
                    )
                ),
                fn ($value) => $value > 0
            )
        );

        $prodiIds = array_values(
            array_filter(
                array_map(
                    'intval',
                    $this->normalizeFilter(
                        $request->query('prodi_id')
                    )
                ),
                fn ($value) => $value > 0
            )
        );

        /*
        |--------------------------------------------------------------------------
        | QUERY DASAR
        |--------------------------------------------------------------------------
        */

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
            ->where(
                'status_workflow',
                'Selesai'
            )
            ->whereIn(
                'jenis_dokumen_id',
                $types[$type]
            );

        /*
        |--------------------------------------------------------------------------
        | FILTER MITRA
        |--------------------------------------------------------------------------
        */

        if (!empty($mitraIds)) {
            $query->whereIn(
                'mitra_id',
                $mitraIds
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER BIDANG
        |--------------------------------------------------------------------------
        */

        if (!empty($bidangIds)) {
            $query->whereIn(
                'bidang_id',
                $bidangIds
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TAHUN
        |--------------------------------------------------------------------------
        */

        if (!empty($tahunValues)) {
            $query->whereIn(
                'tahun',
                $tahunValues
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER NEGARA
        |--------------------------------------------------------------------------
        */

        if (!empty($negaraIds)) {

            $query->whereHas(
                'mitra',
                function (Builder $q) use ($negaraIds) {

                    $q->whereIn(
                        'negara_id',
                        $negaraIds
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS
        |--------------------------------------------------------------------------
        |
        | Kolom jenis berada di tabel kerjasama.
        |
        */

        if (!empty($jenisValues)) {

            $query->whereIn(
                'jenis',
                $jenisValues
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if (!empty($statuses)) {

            $today = today();

            $oneMonthFromNow = today()->addMonth();

            $query->where(function (Builder $q) use (
                $statuses,
                $today,
                $oneMonthFromNow
            ) {

                foreach ($statuses as $statusValue) {

                    switch ($statusValue) {

                        /*
                        |------------------------------------------------------
                        | ACTIVE
                        |------------------------------------------------------
                        */

                        case 'active':

                            $q->orWhere(function (
                                Builder $subQuery
                            ) use (
                                $oneMonthFromNow
                            ) {

                                $subQuery
                                    ->whereNull(
                                        'tanggal_akhir'
                                    )
                                    ->orWhereDate(
                                        'tanggal_akhir',
                                        '>',
                                        $oneMonthFromNow
                                    );
                            });

                            break;

                        /*
                        |------------------------------------------------------
                        | EXPIRING
                        |------------------------------------------------------
                        */

                        case 'expiring':

                            $q->orWhere(function (
                                Builder $subQuery
                            ) use (
                                $today,
                                $oneMonthFromNow
                            ) {

                                $subQuery
                                    ->whereNotNull(
                                        'tanggal_akhir'
                                    )
                                    ->whereDate(
                                        'tanggal_akhir',
                                        '>=',
                                        $today
                                    )
                                    ->whereDate(
                                        'tanggal_akhir',
                                        '<=',
                                        $oneMonthFromNow
                                    );
                            });

                            break;

                        /*
                        |------------------------------------------------------
                        | EXPIRED
                        |------------------------------------------------------
                        */

                        case 'expired':

                            $q->orWhere(function (
                                Builder $subQuery
                            ) use ($today) {

                                $subQuery
                                    ->whereNotNull(
                                        'tanggal_akhir'
                                    )
                                    ->whereDate(
                                        'tanggal_akhir',
                                        '<',
                                        $today
                                    );
                            });

                            break;
                    }
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS DOKUMEN
        |--------------------------------------------------------------------------
        */

        if (!empty($jenisDokumen)) {

            $allowedTypes = $types[$type];

            $filteredTypes = array_intersect(
                $jenisDokumen,
                $allowedTypes
            );

            if (empty($filteredTypes)) {

                $query->whereRaw('1 = 0');

            } else {

                $query->whereIn(
                    'jenis_dokumen_id',
                    $filteredTypes
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PROGRAM STUDI
        |--------------------------------------------------------------------------
        */

        if (!empty($prodiIds)) {

            $query->whereHas(
                'prodis',
                function (Builder $q) use ($prodiIds) {

                    $q->whereKey(
                        $prodiIds
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA
        |--------------------------------------------------------------------------
        */

        $documents = $query
            ->orderByDesc(
                'tanggal_awal'
            )
            ->orderBy(
                'judul'
            )
            ->get()
            ->map(
                fn (Kerjasama $document): array =>
                    $this->transform($document)
            );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Data ' .
                strtoupper($type) .
                ' berhasil diambil.',

            'filters' => [

                'jenis' => $type,

                'mitra_id' => $mitraIds,

                'negara_id' => $negaraIds,

                'bidang_id' => $bidangIds,

                'tahun' => $tahunValues,

                'jenis_mitra' => $jenisValues,

                'status' => $statuses,

                'prodi_id' => $prodiIds,

                'jenis_dokumen_id' => $jenisDokumen,
            ],

            'total' => $documents->count(),

            'data' => $documents,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSFORM DATA
    |--------------------------------------------------------------------------
    */

    private function transform(
        Kerjasama $document
    ): array {

        return [

            'id' => $document->id,

            'jenis_dokumen' =>
                $document->jenisDokumen?->nama,

            'jenis' => $document->jenis,

            'judul' => $document->judul,

            'nomor_dokumen' =>
                $document->nomor_dokumen,

            'tahun' => $document->tahun,

            'tanggal_awal' =>
                $document->tanggal_awal?->toDateString(),

            'tanggal_akhir' =>
                $document->tanggal_akhir?->toDateString(),

            'status' => $document->status,

            /*
            |--------------------------------------------------------------------------
            | MITRA
            |--------------------------------------------------------------------------
            */

            'mitra' => $document->mitra
                ? [

                    'id' =>
                        $document->mitra->id,

                    'nama_mitra' =>
                        $document->mitra->nama_mitra,

                    'kategori' =>
                        $document->mitra
                            ->kategori
                            ?->kategori,

                    'negara' =>
                        $document->mitra
                            ->negara
                            ?->nama_negara
                            ?? 'Indonesia',

                    'telepon' =>
                        $document->mitra->telepon,

                    'email' =>
                        $document->mitra->email,

                    'alamat' =>
                        $document->mitra->alamat,

                    'provinsi' =>
                        $document->mitra
                            ->provinsiModel
                            ?->nama_provinsi,

                    'kota' =>
                        $document->mitra
                            ->kotaModel
                            ?->nama_kota,

                    'pic' =>
                        $document->mitra->pic,

                ]
                : null,

            /*
            |--------------------------------------------------------------------------
            | LOCATION
            |--------------------------------------------------------------------------
            */

            'provinsi' =>
                $document->provinsi
                    ?->nama_provinsi,

            'kota' =>
                $document->kota
                    ?->nama_kota,

            /*
            |--------------------------------------------------------------------------
            | BIDANG
            |--------------------------------------------------------------------------
            */

            'bidang' =>
                $document->bidang
                    ?->bidang_kerjasama,

            /*
            |--------------------------------------------------------------------------
            | PROGRAM STUDI
            |--------------------------------------------------------------------------
            */

            'program_studi' =>
                $document->prodis
                    ->pluck('nama_prodi')
                    ->values(),

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT LINKS
            |--------------------------------------------------------------------------
            */

            'dokumen' =>
                $document->link_dokumen,

            'link_perbaikan' =>
                $document->link_perbaikan,

            'bukti_kegiatan' =>
                $document->bukti_kegiatan,

            'link_laporan_kegiatan' =>
                $document->link_laporan_kegiatan,

            /*
            |--------------------------------------------------------------------------
            | PARENT DOCUMENT
            |--------------------------------------------------------------------------
            */

            'parent' => $document->parent
                ? [

                    'id' =>
                        $document->parent->id,

                    'jenis_dokumen_id' =>
                        $document->parent->jenis_dokumen_id,

                    'judul' =>
                        $document->parent->judul,

                ]
                : null,
        ];
    }
}