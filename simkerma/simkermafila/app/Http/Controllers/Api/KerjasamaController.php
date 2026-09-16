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
    | MOU
    |--------------------------------------------------------------------------
    */

    public function mou(Request $request): JsonResponse
    {
        return $this->index($request, 'mou');
    }

    /*
    |--------------------------------------------------------------------------
    | MOA
    |--------------------------------------------------------------------------
    */

    public function moa(Request $request): JsonResponse
    {
        return $this->index($request, 'moa');
    }

    /*
    |--------------------------------------------------------------------------
    | IA
    |--------------------------------------------------------------------------
    */

    public function ia(Request $request): JsonResponse
    {
        return $this->index($request, 'ia');
    }

    /*
    |--------------------------------------------------------------------------
    | PKS / SPK
    |--------------------------------------------------------------------------
    */

    public function pks(Request $request): JsonResponse
    {
        return $this->index($request, 'pks');
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

        /*
        |--------------------------------------------------------------------------
        | JENIS DOKUMEN BERDASARKAN ENDPOINT
        |--------------------------------------------------------------------------
        */

        $types = [
            'mou' => [1],
            'moa' => [2],
            'ia'  => [4],
            'pks' => [3, 5],
        ];

        /*
        |--------------------------------------------------------------------------
        | VALIDASI TYPE
        |--------------------------------------------------------------------------
        */

        if (! isset($types[$type])) {
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

        $mitraId = $request->query('mitra_id');

        $bidangId = $request->query('bidang_id');

        $tahun = $request->query('tahun');

        $negaraId = $request->query('negara_id');

        $jenis = $request->query('jenis');

        $status = $request->query(
            'status',
            $request->query('status_kerjasama')
        );

        $jenisDokumen = $request->query(
            'jenis_dokumen_id',
            []
        );

        $prodiId = $request->query(
            'prodi_id',
            []
        );

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI PRODI ID
        |--------------------------------------------------------------------------
        |
        | Support:
        |
        | ?prodi_id=1
        |
        | ?prodi_id[]=1&prodi_id[]=2
        |
        */

        if (! is_array($prodiId)) {
            $prodiId = [$prodiId];
        }

        $prodiId = array_values(
            array_filter(
                array_map('intval', $prodiId),
                fn ($value) => $value > 0
            )
        );

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI JENIS DOKUMEN
        |--------------------------------------------------------------------------
        */

        if (! is_array($jenisDokumen)) {
            $jenisDokumen = [$jenisDokumen];
        }

        $jenisDokumen = array_values(
            array_filter(
                array_map('intval', $jenisDokumen),
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

        if (
            $mitraId !== null &&
            $mitraId !== ''
        ) {

            if (is_array($mitraId)) {

                $mitraIds = array_values(
                    array_filter(
                        $mitraId,
                        fn ($value) =>
                            $value !== null &&
                            $value !== ''
                    )
                );

                if (! empty($mitraIds)) {
                    $query->whereIn(
                        'mitra_id',
                        $mitraIds
                    );
                }

            } else {

                $query->where(
                    'mitra_id',
                    $mitraId
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER BIDANG
        |--------------------------------------------------------------------------
        */

        if (
            $bidangId !== null &&
            $bidangId !== ''
        ) {

            if (is_array($bidangId)) {

                $bidangIds = array_values(
                    array_filter(
                        $bidangId,
                        fn ($value) =>
                            $value !== null &&
                            $value !== ''
                    )
                );

                if (! empty($bidangIds)) {
                    $query->whereIn(
                        'bidang_id',
                        $bidangIds
                    );
                }

            } else {

                $query->where(
                    'bidang_id',
                    $bidangId
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TAHUN
        |--------------------------------------------------------------------------
        */

        if (
            $tahun !== null &&
            $tahun !== ''
        ) {

            if (is_array($tahun)) {

                $tahunValues = array_values(
                    array_filter(
                        $tahun,
                        fn ($value) =>
                            $value !== null &&
                            $value !== ''
                    )
                );

                if (! empty($tahunValues)) {
                    $query->whereIn(
                        'tahun',
                        $tahunValues
                    );
                }

            } else {

                $query->where(
                    'tahun',
                    $tahun
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER NEGARA
        |--------------------------------------------------------------------------
        |
        | negara_id berada di tabel mitra.
        |
        */

        if (
            $negaraId !== null &&
            $negaraId !== ''
        ) {

            if (is_array($negaraId)) {

                $negaraIds = array_values(
                    array_filter(
                        $negaraId,
                        fn ($value) =>
                            $value !== null &&
                            $value !== ''
                    )
                );

                if (! empty($negaraIds)) {

                    $query->whereHas(
                        'mitra',
                        function (Builder $mitraQuery) use ($negaraIds) {

                            $mitraQuery->whereIn(
                                'negara_id',
                                $negaraIds
                            );
                        }
                    );
                }

            } else {

                $query->whereHas(
                    'mitra',
                    function (Builder $mitraQuery) use ($negaraId) {

                        $mitraQuery->where(
                            'negara_id',
                            $negaraId
                        );
                    }
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | ?jenis=Dalam%20Negeri
        |
        | atau:
        |
        | ?jenis[]=Dalam%20Negeri
        | &jenis[]=Luar%20Negeri
        |
        */

        if (
            $jenis !== null &&
            $jenis !== ''
        ) {

            if (is_array($jenis)) {

                $jenisValues = array_values(
                    array_filter(
                        $jenis,
                        fn ($value) =>
                            $value !== null &&
                            $value !== ''
                    )
                );

                if (! empty($jenisValues)) {
                    $query->whereIn(
                        'jenis',
                        $jenisValues
                    );
                }

            } else {

                $query->where(
                    'jenis',
                    $jenis
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        |
        | active
        | expiring
        | expired
        |
        */

        if (
            $status !== null &&
            $status !== ''
        ) {

            $statuses = is_array($status)
                ? $status
                : [$status];

            $statuses = array_values(
                array_filter(
                    $statuses,
                    fn ($value) =>
                        $value !== null &&
                        $value !== ''
                )
            );

            if (! empty($statuses)) {

                $today = today();

                $oneMonthFromNow = today()->addMonth();

                $query->where(function (
                    Builder $statusQuery
                ) use (
                    $statuses,
                    $today,
                    $oneMonthFromNow
                ) {

                    foreach ($statuses as $statusValue) {

                        switch ($statusValue) {

                            /*
                            |--------------------------------------------------------------------------
                            | ACTIVE
                            |--------------------------------------------------------------------------
                            */

                            case 'active':

                                $statusQuery->orWhere(
                                    function (Builder $q) use (
                                        $oneMonthFromNow
                                    ) {

                                        $q->whereNull(
                                            'tanggal_akhir'
                                        )
                                        ->orWhereDate(
                                            'tanggal_akhir',
                                            '>',
                                            $oneMonthFromNow
                                        );
                                    }
                                );

                                break;

                            /*
                            |--------------------------------------------------------------------------
                            | EXPIRING
                            |--------------------------------------------------------------------------
                            */

                            case 'expiring':

                                $statusQuery->orWhere(
                                    function (Builder $q) use (
                                        $today,
                                        $oneMonthFromNow
                                    ) {

                                        $q->whereNotNull(
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
                                    }
                                );

                                break;

                            /*
                            |--------------------------------------------------------------------------
                            | EXPIRED
                            |--------------------------------------------------------------------------
                            */

                            case 'expired':

                                $statusQuery->orWhere(
                                    function (Builder $q) use ($today) {

                                        $q->whereNotNull(
                                            'tanggal_akhir'
                                        )
                                        ->whereDate(
                                            'tanggal_akhir',
                                            '<',
                                            $today
                                        );
                                    }
                                );

                                break;
                        }
                    }
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS DOKUMEN
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | ?jenis_dokumen_id[]=3
        |
        | ?jenis_dokumen_id[]=3&jenis_dokumen_id[]=5
        |
        */

        if (! empty($jenisDokumen)) {

            $query->whereIn(
                'jenis_dokumen_id',
                $jenisDokumen
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PROGRAM STUDI
        |--------------------------------------------------------------------------
        |
        | Ini bagian yang diperbaiki.
        |
        | Contoh:
        |
        | ?prodi_id[]=1&prodi_id[]=2
        |
        | Artinya:
        |
        | Dokumen memiliki Prodi 1 ATAU Prodi 2.
        |
        */

        if (! empty($prodiId)) {

            $query->whereHas(
                'prodis',
                function (Builder $prodiQuery) use ($prodiId) {

                    /*
                    |--------------------------------------------------------------------------
                    | PENTING
                    |--------------------------------------------------------------------------
                    |
                    | Jangan hard-code:
                    |
                    | master_program_studi.id
                    |
                    | whereKey() otomatis menggunakan primary key
                    | dari model Program Studi.
                    |
                    */

                    $prodiQuery->whereKey(
                        $prodiId
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

                'jenis' =>
                    $type,

                'mitra_id' =>
                    $mitraId,

                'negara_id' =>
                    $negaraId,

                'bidang_id' =>
                    $bidangId,

                'tahun' =>
                    $tahun,

                'status' =>
                    $status,

                'prodi_id' =>
                    $prodiId,

                'jenis_dokumen_id' =>
                    $jenisDokumen,
            ],

            'total' =>
                $documents->count(),

            'data' =>
                $documents,
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

            'id' =>
                $document->id,

            'jenis_dokumen' =>
                $document->jenisDokumen?->nama,

            'jenis' =>
                $document->jenis,

            'judul' =>
                $document->judul,

            'nomor_dokumen' =>
                $document->nomor_dokumen,

            'tahun' =>
                $document->tahun,

            'tanggal_awal' =>
                $document->tanggal_awal?->toDateString(),

            'tanggal_akhir' =>
                $document->tanggal_akhir?->toDateString(),

            'status' =>
                $document->status,

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