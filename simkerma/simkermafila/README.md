# SIMKERMA

SIMKERMA adalah aplikasi pengelolaan data kerja sama, mitra, dan dokumen kerja sama berbasis Laravel dan Filament. Fitur yang tersedia mencakup pencatatan dokumen, pelacakan kerja sama, dashboard, ekspor data, serta pengisian otomatis data MoU dari PDF.

## Persyaratan

- PHP 8.2 atau lebih baru dan Composer
- Node.js dan npm
- Database yang dikonfigurasi di `.env` (SQLite dan MySQL tersedia pada konfigurasi aplikasi)
- Kredensial Google Drive untuk menyimpan berkas dokumen
- Poppler (`pdftotext`, `pdftoppm`, dan `pdfinfo`) serta Tesseract dengan data bahasa `ind` dan `eng` untuk autofill PDF
- Server GPU terpisah untuk Unlimited-OCR (opsional; digunakan untuk autofill MoA/LoC/PKS/SPK)
- Ollama bersifat opsional; tanpa Ollama, ekstraksi lokal tetap dapat mengisi field yang dikenali

## Menyiapkan aplikasi

1. Pasang dependensi PHP dan JavaScript:

   ```sh
   composer install
   npm install
   ```

2. Siapkan `.env` berdasarkan konfigurasi lingkungan Anda. Atur sekurangnya `APP_KEY`, koneksi database (`DB_*`), dan kredensial Google Drive (`GOOGLE_DRIVE_CLIENT_ID`, `GOOGLE_DRIVE_CLIENT_SECRET`, `GOOGLE_DRIVE_REFRESH_TOKEN`, serta `GOOGLE_DRIVE_FOLDER_ID`). Jangan masukkan kredensial ke Git.

3. Buat application key, jalankan migrasi, dan siapkan aset:

   ```sh
   php artisan key:generate
   php artisan migrate
   npm run build
   ```

   Pada database baru, jalankan `php artisan db:seed` bila data master awal diperlukan.

4. Jalankan aplikasi secara lokal:

   ```sh
   php artisan serve
   ```

Konfigurasikan web server dan PHP agar dapat menulis ke direktori `storage` dan `bootstrap/cache`. Pada instalasi Laragon, jalankan perintah dari direktori proyek menggunakan terminal Laragon.

## Privasi dan pengisian otomatis MoU

Pilih **MoU** lalu unggah PDF. Aplikasi memproses file di server: teks dicoba dibaca dengan Poppler, dan bila diperlukan halaman awal PDF dirender dengan Poppler lalu diproses dengan Tesseract OCR. Batas ukuran PDF adalah 20 MB; OCR memproses paling banyak 3 halaman.

File PDF disimpan pada disk Google Drive yang dikonfigurasi. Untuk ekstraksi lokal, PDF diproses di server; **file PDF tidak dikirim ke Ollama**. Namun, bila Ollama diaktifkan dan hasil lokal masih memerlukan bantuan klasifikasi, hingga 12.000 karakter teks hasil ekstraksi dapat dikirim ke URL `OLLAMA_URL`. Secara default URL tersebut adalah `http://127.0.0.1:11434`. Jika memakai endpoint lain, pastikan endpoint itu tepercaya dan sesuai kebijakan privasi organisasi.

Penggunaan AI dikendalikan dua tingkat: pengguna harus mencentang **Gunakan AI** di formulir MoU, dan Ollama juga harus diizinkan oleh konfigurasi server. Tanpa mencentang pilihan tersebut, ekstraksi tetap berjalan secara lokal dan tidak ada teks yang dikirim ke Ollama.

Untuk mengizinkan penggunaan Ollama pada aplikasi, atur:

```dotenv
OLLAMA_ENABLED=true
```

Atur `OLLAMA_ENABLED=false` untuk menonaktifkan Ollama secara global; pilihan **Gunakan AI** tidak akan ditampilkan dan tidak ada permintaan Ollama yang dikirim. Perubahan `.env` berlaku di seluruh aplikasi. Setelah mengubahnya, perbarui konfigurasi Laravel yang di-cache, misalnya dengan `php artisan config:clear`.

Autofill hanya mengisi field yang masih kosong. Nilai yang sudah diketik atau dipilih pengguna tidak ditimpa. Periksa hasil ekstraksi sebelum menyimpan karena OCR maupun klasifikasi dokumen dapat keliru.

### Konfigurasi ekstraksi PDF

Pasang Poppler dan Tesseract pada server aplikasi, lalu atur lokasi executable dan data bahasa di `.env`. Contoh untuk Windows/Laragon:

```dotenv
PDFTOTEXT_BINARY="C:/path/to/poppler/Library/bin/pdftotext.exe"
PDF_RENDERER_BINARY="C:/path/to/poppler/Library/bin/pdftoppm.exe"
PDFINFO_BINARY="C:/path/to/poppler/Library/bin/pdfinfo.exe"
TESSERACT_BINARY="C:/Program Files/Tesseract-OCR/tesseract.exe"
TESSERACT_LANGUAGES=ind+eng
PDF_RENDERER_DPI=200
```

Ganti path contoh dengan lokasi instalasi yang sebenarnya. Pastikan proses PHP dapat menjalankan executable tersebut dan membaca data bahasa Tesseract. `pdftoppm` diperlukan untuk OCR PDF hasil pemindaian; `pdftotext` saja tidak cukup.

Untuk menyediakan Ollama bagi pengguna yang memilih **Gunakan AI**, atur opsi berikut sesuai server/model yang dikelola:

```dotenv
OLLAMA_ENABLED=true
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:7b
OLLAMA_CONNECTION_TIMEOUT=15
OLLAMA_KEEP_ALIVE=30m
```

Jangan arahkan `OLLAMA_URL` ke layanan publik atau tidak tepercaya tanpa menilai kebijakan pengiriman teks dan pengamanan koneksinya.

## Unlimited-OCR untuk MoA / LoC / PKS / SPK

Form tambah dokumen MoA/LoC/PKS/SPK dapat memakai Unlimited-OCR 2 untuk membaca PDF dan mengisi judul, nomor Polinema, nomor mitra, serta tanggal. PDF dirender menjadi gambar oleh Poppler di server Laravel, lalu gambar dikirim ke endpoint SGLang yang kompatibel dengan OpenAI. Field yang sudah berisi nilai tidak ditimpa. Action Gemini yang sudah ada tetap tersedia.

Model ini tidak diunduh atau dijalankan oleh Laravel/Windows. Jalankan SGLang dan Unlimited-OCR 2 di server Linux dengan NVIDIA GPU mengikuti instruksi [resmi Unlimited-OCR](https://github.com/baidu/Unlimited-OCR#sglang). Model checkpoint diunduh oleh Hugging Face ketika server model pertama kali dijalankan; contoh resmi merekomendasikan GPU kelas A800 80 GB. Pastikan server model hanya dapat diakses dari jaringan tepercaya.

Atur URL API SGLang dan aktifkan fitur di `.env` Laravel:

```dotenv
UNLIMITED_OCR_ENABLED=true
UNLIMITED_OCR_URL=http://alamat-server-gpu:10000/v1/chat/completions
UNLIMITED_OCR_TOKEN=
UNLIMITED_OCR_MODEL=Unlimited-OCR-2
UNLIMITED_OCR_MAX_PAGES=10
UNLIMITED_OCR_DPI=150
UNLIMITED_OCR_TOKENS_PER_PAGE=8000
UNLIMITED_OCR_CONNECT_TIMEOUT=10
UNLIMITED_OCR_TIMEOUT=600
```

`UNLIMITED_OCR_MAX_PAGES` membatasi jumlah halaman per PDF (nilai `0` menonaktifkan batas di Laravel, tetapi tetap tunduk pada kapasitas dan konfigurasi server GPU). Poppler `pdfinfo` dan `pdftoppm` harus tersedia di server Laravel; path executable dapat diatur lewat `PDFINFO_BINARY` dan `PDF_RENDERER_BINARY`. Setelah konfigurasi berubah, jalankan `php artisan config:clear`. Jaga token dan alamat internal server di `.env`, bukan di Git. Matikan fitur dengan `UNLIMITED_OCR_ENABLED=false` bila model tidak tersedia.

## Operasi

- Jalankan tes: `php artisan test`
- Jadwal pengecekan kerja sama kedaluwarsa didaftarkan harian di `routes/console.php`. Pada server produksi, jalankan Laravel scheduler setiap menit sesuai dokumentasi Laravel, atau gunakan `php artisan schedule:work` untuk pengembangan lokal.
- Setelah perubahan environment, jalankan `php artisan config:clear` bila konfigurasi di-cache.
- Simpan backup database dan atur backup Google Drive sesuai kebijakan organisasi.

## Dokumentasi Laravel

Panduan framework tersedia di [dokumentasi Laravel](https://laravel.com/docs) dan dokumentasi panel admin di [dokumentasi Filament](https://filamentphp.com/docs).
