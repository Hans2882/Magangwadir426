<?php

namespace App\Livewire\Landing;

use Livewire\Component;
use App\Models\Kerjasama;
use App\Models\KuisionerKepuasan;
use App\Models\MasterProgramStudi;
use App\Models\Mitra;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;

#[Layout('components.layouts.landing')]
class CaseStudies extends Component
{
    use WithPagination;

    public $activeTab = 'caseStudies';
    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Survey Properties
    #[Validate('required')]
    public $surveyNama = '';
    
    #[Validate('required')]
    public $surveyJabatan = '';
    
    #[Validate('required')]
    public $surveyInstansi = '';
    
    #[Validate('required|email')]
    public $surveyEmail = '';
    
    #[Validate('required')]
    public $surveyTelepon = '';

    #[Validate('required|string')]
    public $surveyNomorDokumen = '';

    public $matchedKerjasamaId = null;
    public $matchedMitraName = null;
    public $suggestions = [];

    #[Validate('required|integer|min:1|max:5')]
    public $surveyKomunikasi = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyProses = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyBantuan = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveySdmProfesionalisme = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyHarapan = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyManfaat = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKembali = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyImplementasi = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyLaporan = null;

    #[Validate('required')]
    public $surveyAlumniAda = '';

    #[Validate('required|integer|min:1|max:5')]
    public $surveyEtika = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKepemimpinan = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyEtosKerja = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKomunikasiAlumni = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKerjasamaTim = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKeahlianBidangIlmu = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyKeahlianBidangIlmuTerapan = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyBahasaAsing = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyTeknologiInformasi = null;
    
    #[Validate('required|integer|min:1|max:5')]
    public $surveyPengembanganDiri = null;

    #[Validate('required|string')]
    public $surveySaranKerjasama = '';
    
    #[Validate('required|string')]
    public $surveySaranAlumni = '';
    
    #[Validate('required')]
    public $surveyProgramStudiAlumni = '';

    protected $messages = [
        'required' => 'This field is required.',
        'email' => 'Email tidak valid.',
        'integer' => 'Pilih angka 1 sampai 5.',
    ];

    public function submitSurvey()
    {
        $this->validate();

        $kerjasama = $this->findKerjasamaByDocument($this->surveyNomorDokumen);
        if (!$kerjasama) {
            $this->addError('surveyNomorDokumen', 'Document not found.');
            return;
        }

        KuisionerKepuasan::create([
            'nama' => $this->surveyNama,
            'jabatan' => $this->surveyJabatan,
            'instansi' => $this->surveyInstansi,
            'email' => $this->surveyEmail,
            'telepon' => $this->surveyTelepon,
            'nomor_dokumen' => $kerjasama->nomor_dokumen,
            'komunikasi' => $this->surveyKomunikasi,
            'proses' => $this->surveyProses,
            'bantuan' => $this->surveyBantuan,
            'sdm_profesionalisme' => $this->surveySdmProfesionalisme,
            'harapan' => $this->surveyHarapan,
            'manfaat' => $this->surveyManfaat,
            'kembali' => $this->surveyKembali,
            'implementasi' => $this->surveyImplementasi,
            'laporan' => $this->surveyLaporan,
            'alumni_ada' => $this->surveyAlumniAda,
            'etika' => $this->surveyEtika,
            'kepemimpinan' => $this->surveyKepemimpinan,
            'etos_kerja' => $this->surveyEtosKerja,
            'komunikasi_alumni' => $this->surveyKomunikasiAlumni,
            'kerjasama_tim' => $this->surveyKerjasamaTim,
            'keahlian_bidang_ilmu' => $this->surveyKeahlianBidangIlmu,
            'keahlian_bidang_ilmu_terapan' => $this->surveyKeahlianBidangIlmuTerapan,
            'bahasa_asing' => $this->surveyBahasaAsing,
            'teknologi_informasi' => $this->surveyTeknologiInformasi,
            'pengembangan_diri' => $this->surveyPengembanganDiri,
            'saran_kerjasama' => $this->surveySaranKerjasama,
            'saran_alumni' => $this->surveySaranAlumni,
            'program_studi_alumni' => $this->surveyProgramStudiAlumni,
        ]);

        session()->flash('success', 'Kuisioner berhasil dikirim. Terima kasih atas partisipasi Anda!');

        $this->reset([
            'surveyNama', 'surveyJabatan', 'surveyInstansi', 'surveyEmail', 'surveyTelepon', 'surveyNomorDokumen',
            'surveyKomunikasi', 'surveyProses', 'surveyBantuan', 'surveySdmProfesionalisme',
            'surveyHarapan', 'surveyManfaat', 'surveyKembali', 'surveyImplementasi', 'surveyLaporan',
            'surveyAlumniAda', 'surveyEtika', 'surveyKepemimpinan', 'surveyEtosKerja',
            'surveyKomunikasiAlumni', 'surveyKerjasamaTim', 'surveyKeahlianBidangIlmu',
            'surveyKeahlianBidangIlmuTerapan', 'surveyBahasaAsing', 'surveyTeknologiInformasi',
            'surveyPengembanganDiri', 'surveySaranKerjasama', 'surveySaranAlumni', 'surveyProgramStudiAlumni'
        ]);

        $this->matchedKerjasamaId = null;
        $this->matchedMitraName = null;
    }

    public function updatedSurveyNomorDokumen()
    {
        $this->resetErrorBag('surveyNomorDokumen');

        $input = trim($this->surveyNomorDokumen);
        $this->matchedKerjasamaId = null;
        $this->suggestions = [];

        if ($input === '') {
            $this->matchedMitraName = null;
            return;
        }

        $matches = $this->findKerjasamaMatches($input);
        if ($matches->isEmpty()) {
            $this->matchedKerjasamaId = null;
            $this->matchedMitraName = null;
            $this->suggestions = [];
            return;
        }

        $first = $matches->first();
        $this->matchedKerjasamaId = $first->id;
        $this->matchedMitraName = $first->mitra?->nama_mitra;
        if ($first->mitra?->nama_mitra) {
            $this->surveyInstansi = $first->mitra->nama_mitra;
        }

        // prepare suggestions array for the view
        $this->suggestions = $matches->map(function ($item) {
            return [
                'id' => $item->id,
                'nomor' => $item->nomor_dokumen,
                'mitra' => $item->mitra?->nama_mitra,
            ];
        })->take(6)->toArray();
    }

    private function findKerjasamaByDocument(string $input): ?Kerjasama
    {
        $clean = strtolower(trim(preg_replace('/\s+/', ' ', $input)));

        return Kerjasama::with('mitra')
            ->whereIn('jenis_dokumen_id', [1, 3, 4])
            ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(nomor_dokumen, "\n", " "), "\r", " "), "  ", " ")) = ?', [$clean])
            ->orWhere(function ($query) use ($clean) {
                $query->whereIn('jenis_dokumen_id', [1, 3, 4])
                    ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(nomor_dokumen, "\n", " "), "\r", " "), "  ", " ")) like ?', ['%' . $clean . '%']);
            })
            ->first();
    }

    private function findKerjasamaMatches(string $input)
    {
        $clean = strtolower(trim(preg_replace('/\s+/', ' ', $input)));

        return Kerjasama::with('mitra')
            ->whereIn('jenis_dokumen_id', [1, 3, 4])
            ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(nomor_dokumen, "\n", " "), "\r", " "), "  ", " ")) like ?', [$clean . '%'])
            ->orWhere(function ($query) use ($clean) {
                $query->whereIn('jenis_dokumen_id', [1, 3, 4])
                    ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(nomor_dokumen, "\n", " "), "\r", " "), "  ", " ")) like ?', ['%' . $clean . '%']);
            })
            ->orderByDesc('tanggal_awal')
            ->limit(6)
            ->get();
    }

    public function selectSuggestion(string $nomor)
    {
        $kerjasama = $this->findKerjasamaByDocument($nomor);
        if (!$kerjasama) return;

        $this->surveyNomorDokumen = $kerjasama->nomor_dokumen;
        $this->matchedKerjasamaId = $kerjasama->id;
        $this->matchedMitraName = $kerjasama->mitra?->nama_mitra;
        if ($kerjasama->mitra?->nama_mitra) {
            $this->surveyInstansi = $kerjasama->mitra->nama_mitra;
        }
        $this->suggestions = [];
    }

    public function render()
    {
        // jenis_dokumen_id = 8 (Case Study), status = Selesai
        $caseStudies = Kerjasama::with(['mitra', 'mitra.negara'])
            ->where('jenis_dokumen_id', 8)
            ->where('status_workflow', 'Selesai')
            ->latest('tanggal_awal')
            ->get();

        $programStudiOptions = MasterProgramStudi::orderBy('nama_prodi')->pluck('nama_prodi')->toArray();

        $mitras = Mitra::with(['negara', 'kategori'])
            ->when($this->search, function ($query) {
                $query->where('nama_mitra', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nama_mitra')
            ->paginate(10);

        return view('livewire.landing.case-studies', [
            'caseStudies' => $caseStudies,
            'programStudiOptions' => $programStudiOptions,
            'mitras' => $mitras
        ]);
    }
}
