<div>
    <div class="header-section">
        <h2>Publikasi Pelaporan Case Study</h2>
        <p>Jelajahi berbagai laporan Case Study yang telah diselesaikan oleh Mitra dan Institusi kami.</p>
    </div>

    <div class="tabbed-panel">
        <div class="tab-list" role="tablist">
            <button wire:click.prevent="$set('activeTab', 'caseStudies')" type="button" class="tab-button {{ $activeTab === 'caseStudies' ? 'active' : '' }}">Case Study</button>
            <button wire:click.prevent="$set('activeTab', 'publikasi')" type="button" class="tab-button {{ $activeTab === 'publikasi' ? 'active' : '' }}">Publikasi</button>
            <button wire:click.prevent="$set('activeTab', 'survey')" type="button" class="tab-button {{ $activeTab === 'survey' ? 'active' : '' }}">Kuisioner Kepuasan</button>
        </div>
    </div>

    @if($activeTab === 'caseStudies')
        <div class="grid-container">
            @forelse($caseStudies as $study)
                <div class="glass-card">
                    <div class="card-header">
                        <span class="badge {{ $study->jenis === 'Dalam Negeri' ? 'badge-dn' : 'badge-ln' }}">
                            <i data-lucide="{{ $study->jenis === 'Dalam Negeri' ? 'building-2' : 'globe-2' }}" class="icon-sm"></i>
                            {{ $study->jenis }}
                        </span>
                        <span class="date">{{ $study->tanggal_awal ? $study->tanggal_awal->format('d F Y') : '-' }}</span>
                    </div>
                    
                    <h3 class="title">{{ $study->judul }}</h3>
                    
                    <div class="mitra-info">
                        <i data-lucide="briefcase" class="icon-md text-muted"></i>
                        <div>
                            <p class="mitra-name">{{ $study->public_mitra_name }}</p>
                            <p class="mitra-country">
                                {{ $study->jenis === 'Luar Negeri' ? ($study->mitra->negara->nama_negara ?? '-') : 'Indonesia' }}
                            </p>
                        </div>
                    </div>

                    @if($study->link_dokumen)
                        <div class="card-footer">
                            @php
                                $path = is_array($study->link_dokumen) ? array_values($study->link_dokumen)[0] : $study->link_dokumen;
                            @endphp
                            <a href="{{ route('view-dokumen', ['path' => $path]) }}" target="_blank" class="btn-primary">
                                <i data-lucide="file-text" class="icon-sm"></i>
                                Lihat Dokumen
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state">
                    <i data-lucide="folder-search" class="empty-icon"></i>
                    <p>Belum ada pelaporan Case Study yang diselesaikan.</p>
                </div>
            @endforelse
        </div>
    @elseif($activeTab === 'publikasi')
        <div class="panel-content">
            <div class="info-card">
                <h3>Publikasi</h3>
                <p>Konten publikasi sedang disusun. Silakan kembali lagi sebentar lagi untuk melihat ringkasan publikasi terbaru dari SIMKERMA.</p>
            </div>
        </div>
    @elseif($activeTab === 'survey')
        <div class="panel-content">
            <div class="survey-card">
                <div class="survey-header">
                    <div>
                        <p class="eyebrow">Kuisioner Kepuasan</p>
                        <h3>Bagikan pengalaman Anda dengan SIMKERMA</h3>
                        <p>Isi kuisioner berikut agar kami dapat meningkatkan layanan dan kemudahan penggunaan platform.</p>
                    </div>
                </div>

                @if(session()->has('success'))
                    <div class="alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form wire:submit.prevent="submitSurvey" class="survey-form">
                    <div class="field-grid">
                        <label>
                            <span>Nama</span>
                            <input type="text" wire:model.defer="surveyNama" placeholder="Nama lengkap" />
                            @error('surveyNama') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Jabatan</span>
                            <input type="text" wire:model.defer="surveyJabatan" placeholder="Jabatan Anda" />
                            @error('surveyJabatan') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Institusi / Afiliasi</span>
                            <input type="text" wire:model.defer="surveyInstansi" placeholder="Institusi atau afiliasi" />
                            @error('surveyInstansi') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Alamat email</span>
                            <input type="email" wire:model.defer="surveyEmail" placeholder="Alamat email" />
                            @error('surveyEmail') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Nomor telepon</span>
                            <input type="text" wire:model.defer="surveyTelepon" placeholder="Nomor telepon" />
                            @error('surveyTelepon') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <p class="rating-instruction">Silakan pilih bintang yang sesuai. Nilai 1 = Sangat Tidak Setuju, nilai 5 = Sangat Setuju.</p>

                    <fieldset class="field-block">
                        <legend class="field-label">Polinema mudah dan cepat dalam berkomunikasi serta merespons kebutuhan kami.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKomunikasi-{{ $i }}" name="surveyKomunikasi" value="{{ $i }}" wire:model="surveyKomunikasi" />
                                <label class="star-label" for="surveyKomunikasi-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKomunikasi') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Polinema memproses naskah kerja sama dengan cepat.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyProses-{{ $i }}" name="surveyProses" value="{{ $i }}" wire:model="surveyProses" />
                                <label class="star-label" for="surveyProses-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyProses') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Polinema memberikan bantuan kepada kami dengan baik saat dibutuhkan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyBantuan-{{ $i }}" name="surveyBantuan" value="{{ $i }}" wire:model="surveyBantuan" />
                                <label class="star-label" for="surveyBantuan-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyBantuan') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">SDM Polinema memiliki kapasitas dan profesionalisme yang baik dalam memberikan pelayanan prima.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveySdmProfesionalisme-{{ $i }}" name="surveySdmProfesionalisme" value="{{ $i }}" wire:model="surveySdmProfesionalisme" />
                                <label class="star-label" for="surveySdmProfesionalisme-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveySdmProfesionalisme') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kerja sama ini sesuai dengan harapan kami.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyHarapan-{{ $i }}" name="surveyHarapan" value="{{ $i }}" wire:model="surveyHarapan" />
                                <label class="star-label" for="surveyHarapan-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyHarapan') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Mitra mendapatkan manfaat dari kerja sama ini.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyManfaat-{{ $i }}" name="surveyManfaat" value="{{ $i }}" wire:model="surveyManfaat" />
                                <label class="star-label" for="surveyManfaat-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyManfaat') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kami akan kembali ke Polinema di masa mendatang untuk kerja sama atau acara lain.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKembali-{{ $i }}" name="surveyKembali" value="{{ $i }}" wire:model="surveyKembali" />
                                <label class="star-label" for="surveyKembali-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKembali') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kerja sama telah diimplementasikan dalam aktivitas yang sesuai dengan MoU yang disepakati bersama.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyImplementasi-{{ $i }}" name="surveyImplementasi" value="{{ $i }}" wire:model="surveyImplementasi" />
                                <label class="star-label" for="surveyImplementasi-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyImplementasi') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Laporan kegiatan kerja sama telah dibuat dan dikomunikasikan dengan baik antara Polinema dan kami.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyLaporan-{{ $i }}" name="surveyLaporan" value="{{ $i }}" wire:model="surveyLaporan" />
                                <label class="star-label" for="surveyLaporan-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyLaporan') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <div class="field-block">
                        <label>
                            <span>Apakah terdapat alumni Polinema yang bekerja di instansi/perusahaan Anda?</span>
                            <select wire:model.defer="surveyAlumniAda">
                                <option value="">Pilih jawaban</option>
                                <option value="Ya">Ya</option>
                                <option value="Tidak">Tidak</option>
                            </select>
                            @error('surveyAlumniAda') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <fieldset class="field-block">
                        <legend class="field-label">Etika (Ethics) – Alumni Polinema memiliki etika kerja yang baik dan sesuai di lingkungan kerja.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyEtika-{{ $i }}" name="surveyEtika" value="{{ $i }}" wire:model="surveyEtika" />
                                <label class="star-label" for="surveyEtika-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyEtika') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kepemimpinan (Leadership) – Alumni Polinema menunjukkan jiwa kepemimpinan yang kuat dalam pekerjaan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKepemimpinan-{{ $i }}" name="surveyKepemimpinan" value="{{ $i }}" wire:model="surveyKepemimpinan" />
                                <label class="star-label" for="surveyKepemimpinan-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKepemimpinan') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Etos Kerja (Work Ethic) – Alumni Polinema menunjukkan etos kerja yang kuat di lingkungan kerja.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyEtosKerja-{{ $i }}" name="surveyEtosKerja" value="{{ $i }}" wire:model="surveyEtosKerja" />
                                <label class="star-label" for="surveyEtosKerja-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyEtosKerja') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kemampuan Berkomunikasi (Communication Skill) – Alumni Polinema berkomunikasi dengan baik dan efektif.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKomunikasiAlumni-{{ $i }}" name="surveyKomunikasiAlumni" value="{{ $i }}" wire:model="surveyKomunikasiAlumni" />
                                <label class="star-label" for="surveyKomunikasiAlumni-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKomunikasiAlumni') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kerjasama Tim (Teamwork) – Alumni Polinema mampu bekerja dalam tim dengan baik.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKerjasamaTim-{{ $i }}" name="surveyKerjasamaTim" value="{{ $i }}" wire:model="surveyKerjasamaTim" />
                                <label class="star-label" for="surveyKerjasamaTim-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKerjasamaTim') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Keahlian Bidang Ilmu (Technical Skill) – Alumni Polinema memiliki kemampuan teknis yang sesuai bidang ilmu.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKeahlianBidangIlmu-{{ $i }}" name="surveyKeahlianBidangIlmu" value="{{ $i }}" wire:model="surveyKeahlianBidangIlmu" />
                                <label class="star-label" for="surveyKeahlianBidangIlmu-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKeahlianBidangIlmu') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Keahlian Bidang Ilmu (Technical Skill) – Alumni Polinema menerapkan kemampuan teknis sesuai bidang ilmu kuliah dalam pekerjaan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyKeahlianBidangIlmuTerapan-{{ $i }}" name="surveyKeahlianBidangIlmuTerapan" value="{{ $i }}" wire:model="surveyKeahlianBidangIlmuTerapan" />
                                <label class="star-label" for="surveyKeahlianBidangIlmuTerapan-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyKeahlianBidangIlmuTerapan') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Kemampuan Berbahasa Asing (Foreign Language Skill Ability) – Alumni Polinema memiliki keterampilan berbahasa asing yang baik untuk menunjang pekerjaan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyBahasaAsing-{{ $i }}" name="surveyBahasaAsing" value="{{ $i }}" wire:model="surveyBahasaAsing" />
                                <label class="star-label" for="surveyBahasaAsing-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyBahasaAsing') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Penggunaan Teknologi Informasi (Information Technology Skill Ability) – Alumni Polinema menggunakan teknologi informasi dengan baik untuk menunjang pekerjaan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyTeknologiInformasi-{{ $i }}" name="surveyTeknologiInformasi" value="{{ $i }}" wire:model="surveyTeknologiInformasi" />
                                <label class="star-label" for="surveyTeknologiInformasi-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyTeknologiInformasi') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <fieldset class="field-block">
                        <legend class="field-label">Pengembangan Diri (Self-Development) – Alumni Polinema selalu melakukan pengembangan diri dan menerapkannya untuk meningkatkan kualitas pekerjaan.</legend>
                        <div class="rating-group">
                            @for($i = 5; $i >= 1; $i--)
                                <input class="rating-input" type="radio" id="surveyPengembanganDiri-{{ $i }}" name="surveyPengembanganDiri" value="{{ $i }}" wire:model="surveyPengembanganDiri" />
                                <label class="star-label" for="surveyPengembanganDiri-{{ $i }}">★</label>
                            @endfor
                        </div>
                        @error('surveyPengembanganDiri') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <div class="field-block">
                        <label>
                            <span>Saran untuk meningkatkan kerja sama Polinema</span>
                            <textarea wire:model.defer="surveySaranKerjasama" rows="4" placeholder="Tulis saran Anda untuk pengembangan kerja sama Polinema..."></textarea>
                            @error('surveySaranKerjasama') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="field-block">
                        <label>
                            <span>Saran untuk meningkatkan kualitas alumni Polinema</span>
                            <textarea wire:model.defer="surveySaranAlumni" rows="4" placeholder="Tulis saran Anda untuk peningkatan kualitas alumni Polinema..."></textarea>
                            @error('surveySaranAlumni') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="field-block">
                        <label>
                            <span>Alumni Polinema yang bekerja di instansi/perusahaan Anda berasal dari program studi</span>
                            <select wire:model.defer="surveyProgramStudiAlumni">
                                <option value="">Pilih program studi</option>
                                @foreach($programStudiOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('surveyProgramStudiAlumni') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <button type="submit" class="btn-submit" wire:loading.attr="disabled" wire:target="submitSurvey">
                        <span wire:loading> Mengirim... </span>
                        <span wire:loading.remove> Kirim Kuisioner </span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <style>
        .header-section {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .header-section p {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .tabbed-panel {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
        }

        .tab-list {
            display: inline-flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(203, 213, 225, 0.7);
            border-radius: 999px;
            padding: 0.5rem;
        }

        .tab-button {
            border: none;
            background: transparent;
            color: #475569;
            padding: 0.8rem 1.4rem;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-button.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.18);
        }

        .panel-content {
            display: grid;
            gap: 1.5rem;
        }

        .info-card,
        .survey-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .info-card h3 {
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        .info-card p {
            color: var(--text-muted);
            line-height: 1.8;
        }

        .survey-header {
            margin-bottom: 1.75rem;
        }

        .eyebrow {
            font-size: 0.8rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 0.75rem;
            display: inline-block;
        }

        .survey-header h3 {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--text-main);
        }

        .survey-header p {
            color: var(--text-muted);
            line-height: 1.8;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #047857;
            padding: 1rem 1.2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .survey-form {
            display: grid;
            gap: 1.25rem;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }

        .field-block,
        .field-grid label {
            display: grid;
            gap: 0.5rem;
        }

        .field-block {
            padding: 1rem 1.1rem;
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.92);
        }

        .field-label,
        label span {
            font-weight: 600;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 16px;
            padding: 1rem 1.1rem;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-main);
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(37, 99, 235, 0.7);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        textarea {
            min-height: 160px;
            resize: vertical;
        }

        .rating-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-start;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .rating-input {
            display: none;
        }

        .star-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            width: 2.6rem;
            height: 2.6rem;
            font-size: 2rem;
            color: rgba(148, 163, 184, 0.9);
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .rating-input:checked + .star-label,
        .rating-input:checked + .star-label ~ .star-label,
        .star-label:hover,
        .star-label:hover ~ .star-label {
            color: #f59e0b;
            transform: translateY(-2px);
        }


        

        .field-error {
            color: #b91c1c;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: fit-content;
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 1.75rem;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.18);
        }

        .survey-toast {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            background: rgba(34, 197, 94, 0.95);
            color: white;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
            transform: translateY(-10px);
        }

        .survey-toast.visible {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-dn {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-ln {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .date {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            flex: 1;
        }

        .mitra-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem;
            background: rgba(248, 250, 252, 0.5);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .mitra-name {
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .mitra-country {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .card-footer {
            margin-top: auto;
        }

        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px dashed #cbd5e1;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            color: #94a3b8;
            margin: 0 auto 1rem;
        }

        .icon-sm { width: 14px; height: 14px; }
        .icon-md { width: 20px; height: 20px; }
        .text-muted { color: var(--text-muted); }
    </style>

</div>
