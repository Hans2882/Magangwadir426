# 🔑 WebAPI Setup Guide - SimKerma Mitra Share

## ⚡ Quick Start

### 1️⃣ Generate API Key (Admin)
1. Buka Filament Admin Panel
2. Di dashboard, lihat tombol **"API Documentation"** warna biru di bagian atas halaman **Data Mitra**
3. Klik tombol tersebut → akan membuka halaman **Settings → API Keys**
4. Klik **"Generate New API Key"**
5. Isi nama key (misal: "Integration Server", "Mobile App", dll)
6. Klik create
7. **Copy dan simpan API key yang di-generate** di tempat aman ✅

> ⚠️ **PENTING**: API key hanya ditampilkan sekali. Jika lupa, generate key baru dan deactivate yang lama.

---

### 2️⃣ Menggunakan API (Developer)

#### Header Authorization (Recommended)
```bash
curl -X GET "https://your-domain.com/api/v1/mitra" \
  -H "X-API-Key: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

#### Query Parameter
```bash
curl "https://your-domain.com/api/v1/mitra?api_key=sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

---

## 📚 Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/mitra` | List semua mitra (paginated) |
| GET | `/api/v1/mitra/{id}` | Detail mitra spesifik |
| GET | `/api/v1/mitra/{id}/kerjasama` | Kerjasama data mitra |
| GET | `/api/v1/api-key/info` | Info API key yang digunakan |

---

## 🔍 Examples

### Contoh 1: Get All Mitra
```bash
curl -X GET "https://your-domain.com/api/v1/mitra?per_page=10" \
  -H "X-API-Key: sk_xxxxx"
```

Response:
```json
{
  "success": true,
  "message": "Data mitra retrieved successfully",
  "data": [
    {
      "id": 1,
      "nama_mitra": "PT. Example",
      "email": "info@example.com",
      "telepon": "021-1234567"
    }
  ],
  "pagination": {
    "total": 150,
    "per_page": 10,
    "current_page": 1,
    "last_page": 15
  }
}
```

### Contoh 2: Get Mitra by Tipe
```bash
# Mitra Luar Negeri
curl "https://your-domain.com/api/v1/mitra?tipe=luar_negeri" \
  -H "X-API-Key: sk_xxxxx"

# Mitra Dalam Negeri
curl "https://your-domain.com/api/v1/mitra?tipe=dalam_negeri" \
  -H "X-API-Key: sk_xxxxx"
```

### Contoh 3: Get Detail Mitra + Kerjasama
```bash
curl "https://your-domain.com/api/v1/mitra/1" \
  -H "X-API-Key: sk_xxxxx"

# Kerjasama dari mitra tersebut
curl "https://your-domain.com/api/v1/mitra/1/kerjasama" \
  -H "X-API-Key: sk_xxxxx"
```

---

## 🛡️ Security Best Practices

✅ **DO:**
- Simpan API key di environment variables atau secrets manager
- Use HTTPS untuk semua request
- Rotate API key secara berkala
- Monitor "Last Used" di halaman API Keys management
- Gunakan API key yang spesifik untuk setiap aplikasi/integration

❌ **DON'T:**
- Commit API key ke version control (git, github, dll)
- Share API key di public channels (Slack, Discord, email, dll)
- Simpan API key di file text biasa
- Gunakan API key langsung di frontend (public)

---

## 🔧 API Key Management

### View/Edit API Key
1. Login ke Filament Admin Panel
2. Settings → API Keys
3. Lihat list semua API keys dengan:
   - Status (Active/Inactive)
   - Last Used timestamp
   - Created date

### Deactivate API Key
1. Buka Settings → API Keys
2. Click edit pada key yang ingin di-deactivate
3. Ubah status menjadi "Inactive"
4. Save

### Delete API Key
1. Buka Settings → API Keys
2. Click action delete (trash icon)
3. Confirm

---

## 💻 Code Examples

### Python
```python
import requests

API_KEY = "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
BASE_URL = "https://your-domain.com/api/v1"

headers = {"X-API-Key": API_KEY}

# Get all mitra
response = requests.get(f"{BASE_URL}/mitra", headers=headers)
data = response.json()

if data['success']:
    for mitra in data['data']:
        print(f"{mitra['nama_mitra']} - {mitra['email']}")
```

### JavaScript
```javascript
const API_KEY = "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
const BASE_URL = "https://your-domain.com/api/v1";

async function getAllMitra() {
  const response = await fetch(`${BASE_URL}/mitra`, {
    headers: { "X-API-Key": API_KEY }
  });
  
  const data = await response.json();
  
  if (data.success) {
    data.data.forEach(mitra => {
      console.log(`${mitra.nama_mitra} - ${mitra.email}`);
    });
  }
}

getAllMitra();
```

### PHP/Laravel
```php
use Illuminate\Support\Facades\Http;

$apiKey = "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

$response = Http::withHeaders([
    'X-API-Key' => $apiKey
])->get('https://your-domain.com/api/v1/mitra');

if ($response->json()['success']) {
    foreach ($response->json()['data'] as $mitra) {
        echo "{$mitra['nama_mitra']} - {$mitra['email']}\n";
    }
}
```

---

## ❓ FAQ

### Q: Bagaimana jika lupa API key?
**A:** Tidak bisa recover API key yang lama. Generate key baru di Settings → API Keys, lalu update di aplikasi Anda. Deactivate API key yang lama jika sudah tidak digunakan.

### Q: Apakah ada rate limiting?
**A:** Saat ini belum ada rate limiting. Jika diperlukan di masa depan, bisa ditambahkan.

### Q: Bagaimana tracking API usage?
**A:** Lihat "Last Used" timestamp di API Keys management untuk mengetahui kapan terakhir API key digunakan.

### Q: Berapa lama API key berlaku?
**A:** API key tidak ada expiry date, tetapi bisa di-deactivate kapan saja. Recommended untuk rotate key setiap 6-12 bulan.

### Q: Apa bedanya endpoint `/api/user` dengan `/api/v1/mitra`?
**A:** `/api/user` menggunakan authentication Sanctum (untuk login system), sedangkan `/api/v1/mitra` menggunakan API Key authentication (untuk external integration).

---

## 📞 Support

Untuk pertanyaan atau issue:
1. Cek WEBAPI_DOCUMENTATION.md untuk detail endpoint
2. Lihat error message di response API
3. Monitor API usage di Settings → API Keys

---

## 📋 Checklist untuk Setup

- [ ] Migrasi database berhasil (`php artisan migrate`)
- [ ] API Key Resource ter-register di Filament
- [ ] Tombol "API Documentation" ada di Data Mitra page
- [ ] Generate test API key di Settings → API Keys
- [ ] Test API endpoint dengan curl/Postman
- [ ] Update dokumentasi project dengan API info
- [ ] Bagikan API documentation ke developer team

---

**Created**: September 1, 2026  
**Version**: v1.0  
**Status**: ✅ Production Ready
