<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | API KEY
        |--------------------------------------------------------------------------
        */

        $apiKey = $request->query('api_key');

        if (
            !$apiKey ||
            $apiKey !== config('services.web_api.key')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak valid.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        $tab = $request->query(
            'tab',
            'dalam_negeri'
        );

        $kategoriId = $request->query(
            'kategori_id'
        );

        $negaraId = $request->query(
            'negara_id'
        );

        $statusKerjasama = $request->query(
            'status_kerjasama'
        );

        $jenisDokumen = $request->query(
            'jenis_dokumen',
            []
        );


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI JENIS DOKUMEN
        |--------------------------------------------------------------------------
        |
        | Karena jenis_dokumen adalah multiple filter:
        |
        | jenis_dokumen[]=1
        | jenis_dokumen[]=4
        |
        */

        if (!is_array($jenisDokumen)) {
            $jenisDokumen = [$jenisDokumen];
        }

        $jenisDokumen = array_values(
            array_filter(
                $jenisDokumen,
                fn ($value) =>
                    $value !== null &&
                    $value !== ''
            )
        );


        /*
        |--------------------------------------------------------------------------
        | QUERY MITRA
        |--------------------------------------------------------------------------
        */

        $query = Mitra::query()
            ->with([
                'negara:id,nama_negara',
                'kategori:id,kategori,bobot',
                'provinsiModel:id,nama_provinsi',
                'kotaModel:id,nama_kota',
            ])
            ->select([
                'id',
                'nama_mitra',
                'kategori_id',
                'negara_id',
                'qs_rank',
                'telepon',
                'email',
                'alamat',
                'provinsi_id',
                'kota_id',
                'pic',
            ]);


        /*
        |--------------------------------------------------------------------------
        | FILTER TAB
        |--------------------------------------------------------------------------
        |
        | Dalam Negeri:
        | negara_id NULL atau < 1
        |
        | Luar Negeri:
        | negara_id >= 1
        |
        */

        if ($tab === 'luar_negeri') {

            $query->where(
                'negara_id',
                '>=',
                1
            );

        } else {

            $query->where(function (Builder $q) {

                $q->whereNull('negara_id')
                    ->orWhere(
                        'negara_id',
                        '<',
                        1
                    );

            });
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER KATEGORI IKU
        |--------------------------------------------------------------------------
        */

        if (
            $kategoriId !== null &&
            $kategoriId !== ''
        ) {

            $query->where(
                'kategori_id',
                $kategoriId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER NEGARA
        |--------------------------------------------------------------------------
        */

        if (
            $negaraId !== null &&
            $negaraId !== ''
        ) {

            $query->where(
                'negara_id',
                $negaraId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS KERJASAMA
        |--------------------------------------------------------------------------
        */

        if ($statusKerjasama) {

            match ($statusKerjasama) {

                /*
                |--------------------------------------------------------------------------
                | BELUM ADA KERJASAMA
                |--------------------------------------------------------------------------
                */

                'none' => $query->whereDoesntHave(
                    'kerjasamas'
                ),


                /*
                |--------------------------------------------------------------------------
                | AKTIF
                |--------------------------------------------------------------------------
                |
                | Sama dengan filter Filament:
                |
                | tanggal_akhir NULL
                | ATAU
                | tanggal_akhir > 1 bulan dari sekarang
                |
                */

                'active' => $query->whereHas(
                    'kerjasamas',
                    function (Builder $k) {

                        $k->where(function (Builder $q) {

                            $q->whereNull(
                                'tanggal_akhir'
                            )
                            ->orWhereDate(
                                'tanggal_akhir',
                                '>',
                                now()->addMonth()
                            );

                        });

                    }
                ),


                /*
                |--------------------------------------------------------------------------
                | AKAN BERAKHIR
                |--------------------------------------------------------------------------
                |
                | tanggal_akhir antara hari ini
                | sampai 1 bulan ke depan.
                |
                */

                'expiring' => $query->whereHas(
                    'kerjasamas',
                    function (Builder $k) {

                        $k->whereNotNull(
                            'tanggal_akhir'
                        )
                        ->whereDate(
                            'tanggal_akhir',
                            '>=',
                            now()
                        )
                        ->whereDate(
                            'tanggal_akhir',
                            '<=',
                            now()->addMonth()
                        );

                    }
                ),


                /*
                |--------------------------------------------------------------------------
                | BERAKHIR
                |--------------------------------------------------------------------------
                |
                | Memiliki kerjasama,
                | tetapi tidak memiliki kerjasama
                | yang masih aktif.
                |
                */

                'expired' => $query
                    ->whereHas(
                        'kerjasamas'
                    )
                    ->whereDoesntHave(
                        'kerjasamas',
                        function (Builder $k) {

                            $k->whereNull(
                                'tanggal_akhir'
                            )
                            ->orWhereDate(
                                'tanggal_akhir',
                                '>=',
                                now()
                            );

                        }
                    ),


                /*
                |--------------------------------------------------------------------------
                | DEFAULT
                |--------------------------------------------------------------------------
                */

                default => null,
            };
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS DOKUMEN
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | jenis_dokumen[]=1
        |
        | atau:
        |
        | jenis_dokumen[]=1&jenis_dokumen[]=4
        |
        */

        if (!empty($jenisDokumen)) {

            $query->whereHas(
                'kerjasamas',
                function (Builder $k) use ($jenisDokumen) {

                    $k->whereIn(
                        'jenis_dokumen_id',
                        $jenisDokumen
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA
        |--------------------------------------------------------------------------
        */

        $mitra = $query
            ->orderBy(
                'nama_mitra'
            )
            ->get()
            ->map(function ($mitra) {

                return [

                    'id' => $mitra->id,

                    'nama_mitra' => $mitra->nama_mitra,

                    'kategori' => $mitra
                        ->kategori
                        ?->kategori,

                    'negara' => $mitra
                        ->negara
                        ?->nama_negara
                        ?? 'Indonesia',

                    'qs_rank' => $mitra->qs_rank,

                    'telepon' => $mitra->telepon,

                    'email' => $mitra->email,

                    'alamat' => $mitra->alamat,

                    'provinsi' => $mitra
                        ->provinsiModel
                        ?->nama_provinsi,

                    'kota' => $mitra
                        ->kotaModel
                        ?->nama_kota,

                    'pic' => $mitra->pic,

                ];

            });


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' => 'Data mitra berhasil diambil.',

            /*
            |--------------------------------------------------------------------------
            | FILTER YANG DIGUNAKAN
            |--------------------------------------------------------------------------
            */

            'filters' => [

                'tab' => $tab,

                'kategori_id' => $kategoriId,

                'negara_id' => $negaraId,

                'status_kerjasama' => $statusKerjasama,

                'jenis_dokumen' => $jenisDokumen,

            ],

            'total' => $mitra->count(),

            'data' => $mitra,

        ]);
    }
}