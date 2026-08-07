<?php

namespace App\Livewire\Landing;

use Livewire\Component;
use App\Models\Kerjasama;
use App\Models\KuisionerKepuasan;
use App\Models\MasterProgramStudi;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.landing')]
class CaseStudies extends Component
{
    public string $activeTab = 'caseStudies';

    public string $surveyNama = '';
    public string $surveyJabatan = '';
    public string $surveyInstansi = '';
    public string $surveyEmail = '';
    public string $surveyTelepon = '';

    public ?int $surveyKomunikasi = null;
    public ?int $surveyProses = null;
    public ?int $surveyBantuan = null;
    public ?int $surveySdmProfesionalisme = null;
    public ?int $surveyHarapan = null;
    public ?int $surveyManfaat = null;
    public ?int $surveyKembali = null;
    public ?int $surveyImplementasi = null;
    public ?int $surveyLaporan = null;

    public ?string $surveyAlumniAda = null;
    public ?int $surveyEtika = null;
    public ?int $surveyKepemimpinan = null;
    public ?int $surveyEtosKerja = null;
    public ?int $surveyKomunikasiAlumni = null;
    public ?int $surveyKerjasamaTim = null;
    public ?int $surveyKeahlianBidangIlmu = null;
    public ?int $surveyKeahlianBidangIlmuTerapan = null;
    public ?int $surveyBahasaAsing = null;
    public ?int $surveyTeknologiInformasi = null;
    public ?int $surveyPengembanganDiri = null;

    public string $surveySaranKerjasama = '';
    public string $surveySaranAlumni = '';
    public string $surveyProgramStudiAlumni = '';

    protected array $rules = [
        'surveyNama' => ['required', 'string', 'max:255'],
        'surveyJabatan' => ['required', 'string', 'max:255'],
        'surveyInstansi' => ['required', 'string', 'max:255'],
        'surveyEmail' => ['required', 'email', 'max:255'],
        'surveyTelepon' => ['required', 'string', 'max:50'],
        'surveyKomunikasi' => ['required', 'integer', 'between:1,5'],
        'surveyProses' => ['required', 'integer', 'between:1,5'],
        'surveyBantuan' => ['required', 'integer', 'between:1,5'],
        'surveySdmProfesionalisme' => ['required', 'integer', 'between:1,5'],
        'surveyHarapan' => ['required', 'integer', 'between:1,5'],
        'surveyManfaat' => ['required', 'integer', 'between:1,5'],
        'surveyKembali' => ['required', 'integer', 'between:1,5'],
        'surveyImplementasi' => ['required', 'integer', 'between:1,5'],
        'surveyLaporan' => ['required', 'integer', 'between:1,5'],
        'surveyAlumniAda' => ['required', 'in:Ya,Tidak'],
        'surveyEtika' => ['required', 'integer', 'between:1,5'],
        'surveyKepemimpinan' => ['required', 'integer', 'between:1,5'],
        'surveyEtosKerja' => ['required', 'integer', 'between:1,5'],
        'surveyKomunikasiAlumni' => ['required', 'integer', 'between:1,5'],
        'surveyKerjasamaTim' => ['required', 'integer', 'between:1,5'],
        'surveyKeahlianBidangIlmu' => ['required', 'integer', 'between:1,5'],
        'surveyKeahlianBidangIlmuTerapan' => ['required', 'integer', 'between:1,5'],
        'surveyBahasaAsing' => ['required', 'integer', 'between:1,5'],
        'surveyTeknologiInformasi' => ['required', 'integer', 'between:1,5'],
        'surveyPengembanganDiri' => ['required', 'integer', 'between:1,5'],
        'surveySaranKerjasama' => ['required', 'string', 'max:1000'],
        'surveySaranAlumni' => ['required', 'string', 'max:1000'],
        'surveyProgramStudiAlumni' => ['required', 'string', 'max:255'],
    ];

    protected array $messages = [
        'required' => 'field is required',
    ];

    protected array $validationAttributes = [
        'surveyNama' => 'Nama',
        'surveyJabatan' => 'Jabatan',
        'surveyInstansi' => 'Institusi / Affiliasi',
        'surveyEmail' => 'Alamat Email',
        'surveyTelepon' => 'Nomor Telepon',
        'surveyKomunikasi' => 'Polinema mudah dan cepat dalam berkomunikasi dan merespon kebutuhan kami',
        'surveyProses' => 'Polinema memproses naskah kerja sama dengan cepat',
        'surveyBantuan' => 'Polinema memberikan bantuan terhadap kami saat dibutuhkan dengan baik',
        'surveySdmProfesionalisme' => 'SDM Polinema memiliki kapasitas dan profesionalisme yang baik dalam memberikan pelayanan prima',
        'surveyHarapan' => 'Kerja sama ini sesuai harapan kami',
        'surveyManfaat' => 'Mitra mendapatkan manfaat dari kerja sama',
        'surveyKembali' => 'Kami akan kembali ke Polinema di masa mendatang untuk kerja sama/acara lain',
        'surveyImplementasi' => 'Kerja sama telah diimplementasikan dalam aktivitas-aktivitas yang sesuai dengan MoU yang telah disepakati bersama',
        'surveyLaporan' => 'Laporan kegiatan kerja sama telah dibuat dan dikomunikasikan dengan baik antara Polinema dengan kami',
        'surveyAlumniAda' => 'Apakah terdapat alumni Polinema yang bekerja di instansi atau perusahaan Anda?',
        'surveyEtika' => 'Etika – Alumni Polinema memiliki etika kerja yang baik.',
        'surveyKepemimpinan' => 'Kepemimpinan – Alumni Polinema menunjukkan jiwa kepemimpinan yang kuat.',
        'surveyEtosKerja' => 'Etos Kerja – Alumni Polinema memiliki etos kerja yang baik.',
        'surveyKomunikasiAlumni' => 'Kemampuan Berkomunikasi – Alumni Polinema memiliki kemampuan komunikasi yang baik.',
        'surveyKerjasamaTim' => 'Kerja Sama Tim – Alumni Polinema mampu bekerja sama dalam tim dengan baik.',
        'surveyKeahlianBidangIlmu' => 'Keahlian Bidang Ilmu – Alumni Polinema memiliki kemampuan teknis yang baik sesuai bidang ilmu.',
        'surveyKeahlianBidangIlmuTerapan' => 'Keahlian Bidang Ilmu Terapan – Alumni Polinema menerapkan kemampuan teknis sesuai bidang studi.',
        'surveyBahasaAsing' => 'Kemampuan Berbahasa Asing – Alumni Polinema mampu menggunakan bahasa asing dengan baik.',
        'surveyTeknologiInformasi' => 'Penggunaan Teknologi Informasi – Alumni Polinema menguasai teknologi informasi untuk menunjang pekerjaan.',
        'surveyPengembanganDiri' => 'Pengembangan Diri – Alumni Polinema terus meningkatkan kompetensinya.',
        'surveySaranKerjasama' => 'Saran untuk meningkatkan kerja sama Polinema.',
        'surveySaranAlumni' => 'Saran untuk meningkatkan kualitas alumni Polinema.',
        'surveyProgramStudiAlumni' => 'Program studi alumni yang bekerja di instansi atau perusahaan Anda.',
    ];

    public function submitSurvey(): void
    {
        $this->validate();

        KuisionerKepuasan::create([
            'nama' => $this->surveyNama,
            'jabatan' => $this->surveyJabatan,
            'instansi' => $this->surveyInstansi,
            'email' => $this->surveyEmail,
            'telepon' => $this->surveyTelepon,
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

        $this->surveyNama = '';
        $this->surveyJabatan = '';
        $this->surveyInstansi = '';
        $this->surveyEmail = '';
        $this->surveyTelepon = '';
        $this->surveyKomunikasi = null;
        $this->surveyProses = null;
        $this->surveyBantuan = null;
        $this->surveySdmProfesionalisme = null;
        $this->surveyHarapan = null;
        $this->surveyManfaat = null;
        $this->surveyKembali = null;
        $this->surveyImplementasi = null;
        $this->surveyLaporan = null;
        $this->surveyAlumniAda = null;
        $this->surveyEtika = null;
        $this->surveyKepemimpinan = null;
        $this->surveyEtosKerja = null;
        $this->surveyKomunikasiAlumni = null;
        $this->surveyKerjasamaTim = null;
        $this->surveyKeahlianBidangIlmu = null;
        $this->surveyKeahlianBidangIlmuTerapan = null;
        $this->surveyBahasaAsing = null;
        $this->surveyTeknologiInformasi = null;
        $this->surveyPengembanganDiri = null;
        $this->surveySaranKerjasama = '';
        $this->surveySaranAlumni = '';
        $this->surveyProgramStudiAlumni = '';

        session()->flash('success', 'Terima kasih! Kuisioner Anda telah dikirim.');
        $this->activeTab = 'survey';
    }

    public function render()
    {
        // jenis_dokumen_id = 8 (Case Study), status = Selesai
        $caseStudies = Kerjasama::with(['mitra', 'mitra.negara'])
            ->where('jenis_dokumen_id', 8)
            ->where('status_workflow', 'Selesai')
            ->latest('tanggal_awal')
            ->get();

        return view('livewire.landing.case-studies', [
            'caseStudies' => $caseStudies,
            'programStudiOptions' => MasterProgramStudi::orderBy('nama_prodi')->pluck('nama_prodi')->toArray(),
        ]);
    }
}
