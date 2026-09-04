<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $apiKey = $request->query('api_key');

    if ($apiKey !== config('services.web_api.key')) {
        return response()->json([
            'success' => false,
            'message' => 'API Key tidak valid.',
        ], 401);
    }

        $mitra = Mitra::query()
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
            ])
            ->orderBy('nama_mitra')
            ->get()
            ->map(function ($mitra) {
                return [
                    'id' => $mitra->id,
                    'nama_mitra' => $mitra->nama_mitra,

                    'kategori' => $mitra->kategori?->kategori,

                    'negara' => $mitra->negara?->nama_negara
                        ?? 'Indonesia',

                    'qs_rank' => $mitra->qs_rank,

                    'telepon' => $mitra->telepon,

                    'email' => $mitra->email,

                    'alamat' => $mitra->alamat,

                    'provinsi' => $mitra->provinsiModel?->nama_provinsi,

                    'kota' => $mitra->kotaModel?->nama_kota,

                    'pic' => $mitra->pic,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil diambil',
            'total' => $mitra->count(),
            'data' => $mitra,
        ]);
    }
}