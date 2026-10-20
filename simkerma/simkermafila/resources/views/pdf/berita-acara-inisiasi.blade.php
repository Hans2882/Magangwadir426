<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara Inisiasi Kerja Sama</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.2;
            margin: 0;
            padding: 25px 40px;
        }
        p {
            margin: 4px 0;
            line-height: 1.25;
        }
        .header {
            border-bottom: 3px solid black;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .header-table td {
            padding: 0;
            border: none;
        }
        .logo-cell {
            width: 3.2cm;
            vertical-align: middle;
            text-align: left;
        }
        .logo-img {
            width: 3.11cm;
            height: 3.19cm;
            display: block;
        }
        .header-text-cell {
            vertical-align: middle;
            text-align: center;
        }
        .kementerian {
            font-size: 16pt;
            margin: 0;
            line-height: 1.15;
            letter-spacing: 0.2px;
        }
        .polinema {
            font-size: 14pt;
            font-weight: bold;
            margin: 2px 0;
            line-height: 1.15;
            letter-spacing: 0.3px;
        }
        .alamat {
            font-size: 12pt;
            margin: 0;
            line-height: 1.15;
        }
        .title {
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }
        .title h3 {
            margin: 0;
            text-decoration: underline;
            font-size: 12pt;
        }
        .title p {
            margin: 2px 0 10px 0;
        }
        .content {
            text-align: justify;
        }
        .identitas {
            margin-left: 25px;
            margin-top: 3px;
            margin-bottom: 6px;
        }
        .identitas table {
            width: 100%;
        }
        .identitas td {
            vertical-align: top;
            padding: 1px 0;
            line-height: 1.2;
        }
        .identitas td:first-child {
            width: 180px; /* Lebar untuk Nama/NIP/Jabatan agar rapi */
        }
        .identitas td:nth-child(2) {
            width: 15px;
        }
        .list-kegiatan {
            margin-top: 3px;
            margin-bottom: 8px;
            padding-left: 20px;
        }
        .list-kegiatan li {
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .signature-section {
            margin-top: 25px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            text-align: center;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
        }
        .signature-space {
            height: 65px;
        }
    </style>
</head>
<body>

    <div class="header">
        @php
            $logoPath = public_path('HitamPutih.jpg');
            if (!file_exists($logoPath)) {
                $logoPath = public_path('favicon.png');
            }
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
            $mime = (pathinfo($logoPath, PATHINFO_EXTENSION) === 'png') ? 'image/png' : 'image/jpeg';
            $logoSrc = 'data:' . $mime . ';base64,' . $logoData;
        @endphp
        <table class="header-table" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="logo-cell">
                    @if($logoData)
                        <img src="{{ $logoSrc }}" class="logo-img" alt="Polinema Logo">
                    @endif
                </td>
                <td class="header-text-cell">
                    <p class="kementerian">KEMENTERIAN PENDIDIKAN TINGGI, SAINS,<br>DAN TEKNOLOGI</p>
                    <p class="polinema">POLITEKNIK NEGERI MALANG</p>
                    <p class="alamat">Jalan Soekarno Hatta Nomor 9 Jatimulyo, Lowokwaru, Malang 65141<br>Telepon (0341) 404424, 404425, Faksimile (0341) 404420<br>Laman www.polinema.ac.id</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="title">
        <h3>BERITA ACARA INISIASI KERJA SAMA</h3>
        <p>Nomor: {{ $record->nomor_dokumen ?? '............................................' }}</p>
    </div>

    <div class="content">
        @php
            \Carbon\Carbon::setLocale('id');
            $date = \Carbon\Carbon::now();
            $hari = $date->translatedFormat('l');
            $tanggal = $date->format('d');
            $bulan = $date->translatedFormat('F');
            $tahun = $date->format('Y');
        @endphp
        <p>Pada hari ini {{ $hari }} tanggal {{ $tanggal }} bulan {{ $bulan }} tahun {{ $tahun }}, kami yang bertanda tangan di bawah ini:</p>

        <div class="identitas">
            <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td>{{ $record->pengusul_nama ?? '-' }}</td>
                </tr>
                <tr>
                    <td>NIP</td>
                    <td>:</td>
                    <td>{{ $record->pengusul_nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td>{{ $record->pengusul_jabatan ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <p>Untuk selanjutnya disebut PIHAK PERTAMA</p>

        <div class="identitas">
            <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td>Prof. Dr. Eng. Rosa Andrie Asmara, S.T., M.T.</td>
                </tr>
                <tr>
                    <td>NIP</td>
                    <td>:</td>
                    <td>198010102005011001</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td>Wakil Direktur IV</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>Jl. Soekarno-Hatta No 9 Kota Malang</td>
                </tr>
            </table>
        </div>

        <p>Untuk selanjutnya disebut PIHAK KEDUA</p>

        <p>Dengan ini menyampaikan bahwa {{ $record->pengusul_jabatan ?? 'PIHAK PERTAMA' }} telah melakukan inisiasi rencana kerja sama dengan pihak:</p>

        <div class="identitas">
            <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>Nama Institusi Mitra</td>
                    <td>:</td>
                    <td>{{ $record->usulan_nama_mitra ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $record->usulan_alamat ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Negara/Instansi</td>
                    <td>:</td>
                    <td>{{ $record->usulanNegara ? $record->usulanNegara->nama_negara : 'Indonesia' }}</td>
                </tr>
            </table>
        </div>

        <p>Adapun bentuk rencana kerja sama yang diusulkan mencakup:</p>
        
        <ol class="list-kegiatan">
            @foreach($kegiatans as $kegiatan)
                <li>{{ $kegiatan->bidang_kerjasama }}</li>
            @endforeach
            @foreach($kegiatanLainnya ?? [] as $lainnya)
                <li>{{ $lainnya }}</li>
            @endforeach
        </ol>

        <p>{{ $record->pengusul_jurusan ?? '-' }} melalui {{ $record->pengusul_prodi ?? '-' }} telah melakukan komunikasi awal dengan pihak mitra dan mendapatkan respon positif. Diharapkan rencana ini dapat ditindaklanjuti ke tahap fasilitasi oleh Wakil Direktur IV untuk proses administrasi kerja sama sesuai prosedur yang berlaku di Politeknik Negeri Malang.</p>

        <p>Demikian berita acara ini dibuat untuk digunakan sebagaimana mestinya.</p>
    </div>

    <div class="signature-section">
        <table class="signature-table" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td></td>
                <td>Malang, {{ $tanggal }} {{ $bulan }} {{ $tahun }}</td>
            </tr>
            <tr>
                <td>{{ $record->pengusul_jabatan ?? 'Pengusul' }}</td>
                <td>Mengetahui,<br>Wakil Direktur IV</td>
            </tr>
            <tr>
                <td class="signature-space"></td>
                <td class="signature-space"></td>
            </tr>
            <tr>
                <td>{{ $record->pengusul_nama ?? '-' }}</td>
                <td>{{ $wadir4['nama'] ?? 'Prof. Dr. Eng. Rosa Andrie Asmara, S.T., M.T.' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer-line" style="position: fixed; bottom: 0px; left: 0px; right: 0px; border-bottom: 3px solid black;"></div>

</body>
</html>
