<?php 
namespace App\Filament\Widgets; 

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class IndonesiaMapWidget extends Widget 
{ 
    protected static ?string $heading = 'Peta Sebaran Kerjasama'; 
    protected static ?int $sort = 1; 
    protected int | string | array $columnSpan = 'full'; 
    protected string $view = 'filament.widgets.indonesia-map-widget'; 

    public array $provinceData = [];

    public function mount()
    {
        $this->provinceData = $this->fetchProvinceData();
    }

    /**
     * Base query for active (Aktif + Akan Berakhir) MoU, PKS, IA records.
     * Location is taken from the kerjasama record itself (kerjasama.provinsi_id /
     * kerjasama.kota_id), NOT from the mitra.
     * Only counts records where the kerjasama has a province set.
     */
    protected function baseQuery()
    {
        return DB::table('kerjasama')
            ->whereIn('kerjasama.jenis_dokumen_id', [1, 3])  // MoU=1, PKS=3
            ->whereNotNull('kerjasama.tanggal_akhir')
            ->whereDate('kerjasama.tanggal_akhir', '>=', now())  // Aktif + Akan Berakhir
            ->whereNotNull('kerjasama.provinsi_id');              // Must have a province
    }

    protected function fetchProvinceData(): array
    {
        $rows = $this->baseQuery()
            ->join('master_provinsi', 'kerjasama.provinsi_id', '=', 'master_provinsi.id')
            ->select(
                'master_provinsi.nama_provinsi',
                'master_provinsi.id as provinsi_id',
                DB::raw('SUM(kerjasama.jenis_dokumen_id = 1) as mou_count'),
                DB::raw('SUM(kerjasama.jenis_dokumen_id = 3) as pks_count'),
                DB::raw('count(kerjasama.id) as total')
            )
            ->groupBy('master_provinsi.id', 'master_provinsi.nama_provinsi')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[strtoupper($row->nama_provinsi)] = [
                'total'     => (int) $row->total,
                'mou_count' => (int) $row->mou_count,
                'pks_count' => (int) $row->pks_count,
            ];
        }
        return $result;
    }

    /**
     * Livewire method called via Alpine to get city data for a specific province.
     */
    public function getCityData(string $provinsiName): array
    {
        $rows = $this->baseQuery()
            ->join('master_kota', 'kerjasama.kota_id', '=', 'master_kota.id')
            ->join('master_provinsi', 'kerjasama.provinsi_id', '=', 'master_provinsi.id')
            ->where(DB::raw('UPPER(master_provinsi.nama_provinsi)'), 'like', '%' . strtoupper($provinsiName) . '%')
            ->select(
                'master_kota.nama_kota',
                DB::raw('SUM(kerjasama.jenis_dokumen_id = 1) as mou_count'),
                DB::raw('SUM(kerjasama.jenis_dokumen_id = 3) as pks_count'),
                DB::raw('count(kerjasama.id) as total')
            )
            ->groupBy('master_kota.id', 'master_kota.nama_kota')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[strtoupper($row->nama_kota)] = [
                'total'     => (int) $row->total,
                'mou_count' => (int) $row->mou_count,
                'pks_count' => (int) $row->pks_count,
            ];
        }
        return $result;
    }
}