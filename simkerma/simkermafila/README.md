# SIMKERMA

SIMKERMA adalah aplikasi pengelolaan data kerja sama, mitra, dan dokumen kerja sama berbasis Laravel dan Filament. Fitur yang tersedia mencakup pencatatan dokumen, pelacakan kerja sama, dashboard, ekspor data, serta pengisian otomatis data MoU dari PDF.

## Persyaratan

- PHP 8.2 atau lebih baru dan Composer
- Node.js dan npm
- Database yang dikonfigurasi di `.env` (SQLite dan MySQL tersedia pada konfigurasi aplikasi)
- Kredensial Google Drive untuk menyimpan berkas dokumen
- Poppler (`pdftotext`, `pdftoppm`, dan `pdfinfo`) serta Tesseract dengan data bahasa `ind` dan `eng` untuk autofill PDF
- Gemini bersifat opsional; tanpa API key Gemini, ekstraksi lokal tetap dapat mengisi field yang dikenali

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

File PDF disimpan pada disk Google Drive yang dikonfigurasi. PDF diproses di server dan **file PDF tidak dikirim ke Gemini**. Jika hasil lokal masih memerlukan bantuan klasifikasi, pengguna dapat mencentang **Gunakan AI** agar hingga 24.000 karakter teks hasil ekstraksi dikirim ke Google Gemini. Tanpa API key Gemini, autofill tetap berjalan secara lokal.

Pengiriman teks ke Gemini hanya dilakukan setelah pengguna mencentang **Gunakan AI** di formulir MoU. Tanpa mencentang pilihan tersebut, ekstraksi berjalan secara lokal dan tidak ada teks yang dikirim ke Gemini.

Untuk mengaktifkan opsi Gemini, atur API key di `.env`:

```dotenv
GEMINI_API_KEY=your-gemini-api-key
```

Tanpa nilai `GEMINI_API_KEY`, pilihan **Gunakan AI** tidak ditampilkan dan teks tidak dikirim ke layanan eksternal. Setelah mengubah `.env`, perbarui konfigurasi Laravel yang di-cache, misalnya dengan `php artisan config:clear`.

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

## Operasi

- Jalankan tes: `php artisan test`
- Jadwal pengecekan kerja sama kedaluwarsa didaftarkan harian di `routes/console.php`. Pada server produksi, jalankan Laravel scheduler setiap menit sesuai dokumentasi Laravel, atau gunakan `php artisan schedule:work` untuk pengembangan lokal.
- Setelah perubahan environment, jalankan `php artisan config:clear` bila konfigurasi di-cache.
- Simpan backup database dan atur backup Google Drive sesuai kebijakan organisasi.

## Dokumentasi Laravel

Panduan framework tersedia di [dokumentasi Laravel](https://laravel.com/docs) dan dokumentasi panel admin di [dokumentasi Filament](https://filamentphp.com/docs).
