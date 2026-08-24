<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara Inisiasi Kerja Sama</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 30px 40px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 90px;
        }
        .header-text {
            margin-left: 100px;
        }
        .kementerian {
            font-size: 14pt;
            margin: 0;
        }
        .polinema {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
        }
        .alamat {
            font-size: 10pt;
            margin: 0;
        }
        .title {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }
        .title h3 {
            margin: 0;
            text-decoration: underline;
            font-size: 12pt;
        }
        .title p {
            margin: 5px 0 20px 0;
        }
        .content {
            text-align: justify;
        }
        .identitas {
            margin-left: 30px;
            margin-bottom: 20px;
        }
        .identitas table {
            width: 100%;
        }
        .identitas td {
            vertical-align: top;
            padding: 2px 0;
        }
        .identitas td:first-child {
            width: 180px; /* Lebar untuk Nama/NIP/Jabatan agar rapi */
        }
        .identitas td:nth-child(2) {
            width: 15px;
        }
        .list-kegiatan {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .signature-section {
            margin-top: 50px;
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
            height: 100px;
        }
    </style>
</head>
<body>

    <div class="header">
        @php
            $logoPath = public_path('favicon.png');
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
            $logoSrc = 'data:image/png;base64,' . $logoData;
        @endphp
        @if($logoData)
            <img src="{{ $logoSrc }}" class="logo" alt="Polinema Logo">
        @endif
        
        <div class="header-text">
            <p class="kementerian">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN,<br>RISET DAN TEKNOLOGI</p>
            <p class="polinema">POLITEKNIK NEGERI MALANG</p>
            <p class="alamat">Jl. Soekarno Hatta No.9 Malang 65141<br>Telp (0341) 404424 - 404425 Fax (0341) 404420<br>http://www.polinema.ac.id</p>
        </div>
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
                <td>Prof. Dr. Eng. Rosa Andrie Asmara, S.T., M.T.</td>
            </tr>
        </table>
    </div>

</body>
</html>
