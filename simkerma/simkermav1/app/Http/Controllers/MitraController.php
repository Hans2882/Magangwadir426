<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MitraController extends Controller
{
    /**
     * Show the Data Mitra page.
     */
    public function index()
    {
        return view('data-mitra.data-mitra');
    }

    /**
     * AJAX endpoint – Mitra Dalam Negeri (server-side DataTables).
     */
    public function dataDalamNegeri(Request $request)
    {
        $draw    = $request->input('draw', 1);
        $start   = (int) $request->input('start', 0);
        $length  = (int) $request->input('length', 10);
        $search  = $request->input('search.value', '');
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = ['id', 'nama_mitra', 'kategori_mitra', 'bidang_kerja_sama', 'nomor_telepon', 'email'];
        $sortCol = $columns[$orderCol] ?? 'id';

        $query = DB::table('mitra');

        $total = $query->count();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mitra',       'like', "%{$search}%")
                  ->orWhere('kategori_mitra', 'like', "%{$search}%")
                  ->orWhere('bidang_kerja_sama', 'like', "%{$search}%")
                  ->orWhere('nomor_telepon',   'like', "%{$search}%")
                  ->orWhere('email',           'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $rows = $query->orderBy($sortCol, $orderDir)
                      ->skip($start)
                      ->take($length)
                      ->get();

        $data = $rows->map(function ($row, $index) use ($start) {
            return [
                'no'                => $start + $index + 1,
                'nama_mitra'        => $row->nama_mitra,
                'kategori_mitra'    => $row->kategori_mitra ?: '-',
                'bidang_kerja_sama' => $row->bidang_kerja_sama ?: '-',
                'nomor_telepon'     => $row->nomor_telepon ?: '-',
                'email'             => $row->email ?: '-',
            ];
        });

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    /**
     * AJAX endpoint – Mitra Luar Negeri (server-side DataTables).
     */
    public function dataLuarNegeri(Request $request)
    {
        $draw    = $request->input('draw', 1);
        $start   = (int) $request->input('start', 0);
        $length  = (int) $request->input('length', 10);
        $search  = $request->input('search.value', '');
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = ['mitra_luar_negeri.id', 'mitra_luar_negeri.nama_mitra', 'negara.nama_negara', 'mitra_luar_negeri.kategori_mitra'];
        $sortCol = $columns[$orderCol] ?? 'mitra_luar_negeri.id';

        $query = DB::table('mitra_luar_negeri')
                   ->leftJoin('negara', 'mitra_luar_negeri.negara_id', '=', 'negara.id')
                   ->select(
                       'mitra_luar_negeri.id',
                       'mitra_luar_negeri.nama_mitra',
                       'negara.nama_negara',
                       'mitra_luar_negeri.kategori_mitra'
                   );

        $total = DB::table('mitra_luar_negeri')->count();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('mitra_luar_negeri.nama_mitra',    'like', "%{$search}%")
                  ->orWhere('negara.nama_negara',            'like', "%{$search}%")
                  ->orWhere('mitra_luar_negeri.kategori_mitra', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $rows = $query->orderBy($sortCol, $orderDir)
                      ->skip($start)
                      ->take($length)
                      ->get();

        $data = $rows->map(function ($row, $index) use ($start) {
            return [
                'no'             => $start + $index + 1,
                'nama_mitra'     => $row->nama_mitra,
                'negara'         => $row->nama_negara ?? '-',
                'kategori_mitra' => $row->kategori_mitra ?: '-',
            ];
        });

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }
}
