<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermintaanKerjasama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermintaanKerjasamaController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_mitra' => 'required|string|max:255|unique:mitra,nama_mitra|unique:permintaan_kerjasamas,nama_mitra',
            'kategori_id' => 'required|exists:master_mitra_iku,id',
            'negara_id' => 'nullable|exists:master_negara,id',
            'qs_rank' => 'nullable|string|max:50',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'provinsi_id' => 'nullable|exists:master_provinsi,id',
            'kota_id' => 'nullable|exists:master_kota,id',
            'pic' => 'nullable|string|max:255',
            'nama_pengusul' => 'nullable|string|max:255',
        ], [
            'nama_mitra.unique' => 'Mitra ini sudah terdaftar di sistem resmi atau sudah ada dalam antrean permintaan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['status_permintaan'] = 'Proses';
            
            $permintaan = PermintaanKerjasama::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan kerjasama berhasil dibuat',
                'data' => $permintaan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
