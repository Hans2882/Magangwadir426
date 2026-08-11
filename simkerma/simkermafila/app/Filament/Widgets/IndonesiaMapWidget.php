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

    protected function fetchProvinceData(): array
    {
        // Only count active MoU (1) and PKS (3), similar to StatusKerjasamaChart
        return DB::table('kerjasama')
            ->join('mitra', 'kerjasama.mitra_id', '=', 'mitra.id')
            ->join('master_provinsi', 'mitra.provinsi_id', '=', 'master_provinsi.id')
            ->whereIn('kerjasama.jenis_dokumen_id', [1, 3])
            ->whereNotNull('kerjasama.tanggal_akhir')
            ->whereDate('kerjasama.tanggal_akhir', '>', now()) // Only Active Kerjasama
            ->select('master_provinsi.nama_provinsi', DB::raw('count(kerjasama.id) as count'))
            ->groupBy('master_provinsi.nama_provinsi')
            ->pluck('count', 'nama_provinsi')
            ->toArray();
    }

    /**
     * Livewire method called via Alpine to get city data for a specific province.
     */
    public function getCityData(string $provinsiName): array
    {
        return DB::table('kerjasama')
            ->join('mitra', 'kerjasama.mitra_id', '=', 'mitra.id')
            ->join('master_kota', 'mitra.kota_id', '=', 'master_kota.id')
            ->join('master_provinsi', 'master_kota.provinsi_id', '=', 'master_provinsi.id')
            ->whereIn('kerjasama.jenis_dokumen_id', [1, 3])
            ->whereNotNull('kerjasama.tanggal_akhir')
            ->whereDate('kerjasama.tanggal_akhir', '>', now()) // Only Active Kerjasama
            ->where('master_provinsi.nama_provinsi', 'like', "%{$provinsiName}%")
            ->select('master_kota.nama_kota', DB::raw('count(kerjasama.id) as count'))
            ->groupBy('master_kota.nama_kota')
            ->pluck('count', 'nama_kota')
            ->toArray();
    }
}