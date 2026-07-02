<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataMouController extends Controller
{
    public function index()
    {
        return view('data-kerjasama.data-mou');
    }

    public function dataDalamNegeri(Request $request)
    {
        $draw     = $request->input('draw', 1);
        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 10);
        $search   = $request->input('search.value', '');
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns  = ['dk.id','dk.judul','m.nama_mitra','dk.nomor_dokumen','dk.tahun','dk.tanggal_awal','dk.tanggal_akhir','dk.status'];
        $sortCol  = $columns[$orderCol] ?? 'dk.id';

        $query = DB::table('dokumen_kerjasama as dk')
            ->leftJoin('mitra as m', 'dk.mitra_id', '=', 'm.id')
            ->where('dk.jenis_dokumen', 'MoU')
            ->select('dk.id','dk.judul','m.nama_mitra','dk.nomor_dokumen',
                     'dk.nomor_polinema','dk.nomor_mitra','dk.tahun',
                     'dk.tanggal_awal','dk.tanggal_akhir','dk.status',
                     'dk.link_perbaikan','dk.bukti_kegiatan');

        $total = DB::table('dokumen_kerjasama')->where('jenis_dokumen', 'MoU')->count();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dk.judul',          'like', "%{$search}%")
                  ->orWhere('m.nama_mitra',    'like', "%{$search}%")
                  ->orWhere('dk.nomor_dokumen','like', "%{$search}%")
                  ->orWhere('dk.tahun',         'like', "%{$search}%")
                  ->orWhere('dk.status',        'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();
        $rows     = $query->orderBy($sortCol, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function ($row, $i) use ($start) {
            return [
                'no'              => $start + $i + 1,
                'judul'           => $row->judul ?: '-',
                'nama_mitra'      => $row->nama_mitra ?: '-',
                'nomor_dokumen'   => $row->nomor_dokumen ?: '-',
                'tahun'           => $row->tahun ?: '-',
                'tanggal_awal'    => $row->tanggal_awal ?: '-',
                'tanggal_akhir'   => $row->tanggal_akhir ?: '-',
                'status'          => $row->status ?: '-',
                '_nomor_polinema' => $row->nomor_polinema ?: '-',
                '_nomor_mitra'    => $row->nomor_mitra ?: '-',
                '_link_perbaikan' => $row->link_perbaikan ?: '-',
                '_bukti_kegiatan' => $row->bukti_kegiatan ?: '-',
            ];
        });

        return response()->json(['draw'=>(int)$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data]);
    }

    public function dataLuarNegeri(Request $request)
    {
        $draw     = $request->input('draw', 1);
        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 10);
        $search   = $request->input('search.value', '');
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns  = ['dln.id','dln.judul','mln.nama_mitra','n.nama_negara','dln.nomor_dokumen','dln.tahun','dln.tanggal_awal','dln.tanggal_akhir','dln.status'];
        $sortCol  = $columns[$orderCol] ?? 'dln.id';

        $query = DB::table('dokumen_kerjasama_ln as dln')
            ->leftJoin('mitra_luar_negeri as mln', 'dln.mitra_id', '=', 'mln.id')
            ->leftJoin('negara as n', 'mln.negara_id', '=', 'n.id')
            ->where('dln.jenis_dokumen', 'MoU')
            ->select('dln.id','dln.judul','mln.nama_mitra','n.nama_negara','dln.nomor_dokumen',
                     'dln.tahun','dln.tanggal_awal','dln.tanggal_akhir','dln.status',
                     'dln.dokumen','dln.link_perbaikan','dln.qs_webometrics_rank','dln.kegiatan');

        $total = DB::table('dokumen_kerjasama_ln')->where('jenis_dokumen', 'MoU')->count();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dln.judul',          'like', "%{$search}%")
                  ->orWhere('mln.nama_mitra',   'like', "%{$search}%")
                  ->orWhere('n.nama_negara',     'like', "%{$search}%")
                  ->orWhere('dln.nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('dln.tahun',         'like', "%{$search}%")
                  ->orWhere('dln.status',        'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();
        $rows     = $query->orderBy($sortCol, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function ($row, $i) use ($start) {
            return [
                'no'              => $start + $i + 1,
                'judul'           => $row->judul ?: '-',
                'nama_mitra'      => $row->nama_mitra ?: '-',
                'negara'          => $row->nama_negara ?? '-',
                'nomor_dokumen'   => $row->nomor_dokumen ?: '-',
                'tahun'           => $row->tahun ?: '-',
                'tanggal_awal'    => $row->tanggal_awal ?: '-',
                'tanggal_akhir'   => $row->tanggal_akhir ?: '-',
                'status'          => $row->status ?: '-',
                '_dokumen'        => $row->dokumen ?: '-',
                '_link_perbaikan' => $row->link_perbaikan ?: '-',
                '_qs_webometrics' => $row->qs_webometrics_rank ?: '-',
                '_kegiatan'       => $row->kegiatan ?: '-',
            ];
        });

        return response()->json(['draw'=>(int)$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data]);
    }
}
