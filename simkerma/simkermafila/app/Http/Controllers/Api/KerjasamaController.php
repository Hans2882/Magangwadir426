<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kerjasama;
use Illuminate\Http\JsonResponse;

class KerjasamaController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Kerjasama::with([
            'mitra',
            'bidang',
            'prodis',
            'jenisDokumen',
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = Kerjasama::with([
            'mitra',
            'bidang',
            'prodis',
            'jenisDokumen',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}