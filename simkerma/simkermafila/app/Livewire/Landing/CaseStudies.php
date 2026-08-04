<?php

namespace App\Livewire\Landing;

use Livewire\Component;
use App\Models\Kerjasama;
use App\Models\Mitra;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('components.layouts.landing')]
class CaseStudies extends Component
{
    use WithPagination;

    public $activeTab = 'case-studies';
    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function render()
    {
        // jenis_dokumen_id = 8 (Case Study), status = Selesai
        $caseStudies = Kerjasama::with(['mitra', 'mitra.negara'])
            ->where('jenis_dokumen_id', 8)
            ->where('status_workflow', 'Selesai')
            ->latest('tanggal_awal')
            ->get();

        $mitras = Mitra::with(['negara', 'kategori'])
            ->when($this->search, function ($query) {
                $query->where('nama_mitra', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nama_mitra')
            ->paginate(10);

        return view('livewire.landing.case-studies', [
            'caseStudies' => $caseStudies,
            'mitras' => $mitras
        ]);
    }
}
