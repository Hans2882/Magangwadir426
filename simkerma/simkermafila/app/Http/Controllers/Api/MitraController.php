<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MitraController extends Controller
{
    /**
     * Get all mitra data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Mitra::with(['negara', 'kategori', 'provinsiModel', 'kotaModel']);

            // Filter berdasarkan kategori
            if ($request->has('kategori_id')) {
                $query->where('kategori_id', $request->kategori_id);
            }

            // Filter berdasarkan negara
            if ($request->has('negara_id')) {
                $query->where('negara_id', $request->negara_id);
            }

            // Filter berdasarkan tipe (dalam_negeri atau luar_negeri)
            if ($request->has('tipe')) {
                if ($request->tipe === 'dalam_negeri') {
                    $query->where(function ($q) {
                        $q->whereNull('negara_id')
                            ->orWhere('negara_id', '<', 1);
                    });
                } elseif ($request->tipe === 'luar_negeri') {
                    $query->where('negara_id', '>=', 1);
                }
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_mitra', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $mitra = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data mitra retrieved successfully',
                'data' => $mitra->items(),
                'pagination' => [
                    'total' => $mitra->total(),
                    'per_page' => $mitra->perPage(),
                    'current_page' => $mitra->currentPage(),
                    'last_page' => $mitra->lastPage(),
                    'from' => $mitra->firstItem(),
                    'to' => $mitra->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve mitra data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single mitra data
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $mitra = Mitra::with(['negara', 'kategori', 'provinsiModel', 'kotaModel', 'kerjasamas'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Mitra data retrieved successfully',
                'data' => $mitra,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mitra not found',
                'error' => 'not_found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve mitra data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get mitra kerjasama data
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getKerjasama(int $id): JsonResponse
    {
        try {
            $mitra = Mitra::findOrFail($id);
            $kerjasama = $mitra->kerjasamas()
                ->with(['jenisDokumen', 'prodis'])
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Mitra kerjasama data retrieved successfully',
                'mitra' => [
                    'id' => $mitra->id,
                    'nama_mitra' => $mitra->nama_mitra,
                ],
                'data' => $kerjasama->items(),
                'pagination' => [
                    'total' => $kerjasama->total(),
                    'per_page' => $kerjasama->perPage(),
                    'current_page' => $kerjasama->currentPage(),
                    'last_page' => $kerjasama->lastPage(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mitra not found',
                'error' => 'not_found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve kerjasama data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get API key info
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getApiKeyInfo(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->api_key_model;

            return response()->json([
                'success' => true,
                'message' => 'API key info retrieved successfully',
                'data' => [
                    'name' => $apiKey->name,
                    'is_active' => $apiKey->is_active,
                    'last_used_at' => $apiKey->last_used_at,
                    'created_at' => $apiKey->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve API key info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
