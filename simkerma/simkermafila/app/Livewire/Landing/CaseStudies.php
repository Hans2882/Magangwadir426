<?php

namespace App\Livewire\Landing;

use Livewire\Component;
use App\Models\Kerjasama;

class CaseStudies extends Component
{
    public function render()
    {
        // jenis_dokumen_id = 8 (Case Study), status = Selesai
        $caseStudies = Kerjasama::with(['mitra', 'mitra.negara'])
            ->where('jenis_dokumen_id', 8)
            ->where('status_workflow', 'Selesai')
            ->latest('tanggal_awal')
            ->get();

        return view('livewire.landing.case-studies', [
            'caseStudies' => $caseStudies
        ])->layout('components.layouts.landing');
    }
}
