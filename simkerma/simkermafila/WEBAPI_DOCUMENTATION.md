# WebAPI Documentation - SimKerma Mitra Share API

## Deskripsi
WebAPI untuk sharing data Mitra dari sistem SIMKERMA dengan keamanan berbasis API Key authentication.

## Setup & Installation

### 1. Database Migration
```bash
php artisan migrate
```

Ini akan membuat tabel `api_keys` untuk menyimpan API key yang di-generate.

### 2. API Key Management
API keys dapat di-generate melalui:
- **Filament Admin Panel**: Settings → API Keys → Generate New API Key
- **Tombol di Data Mitra Page**: Klik tombol "API Documentation" di bagian atas halaman

## Authentication

### Metode 1: Header Authorization
```bash
curl -X GET "https://simkerma.app/api/v1/mitra" \
  -H "X-API-Key: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

### Metode 2: Query Parameter
```bash
curl -X GET "https://simkerma.app/api/v1/mitra?api_key=sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

## API Endpoints

### Base URL
```
https://simkerma.app/api/v1
```

### 1. Get All Mitra (Paginated)
**Endpoint:** `GET /mitra`

**Query Parameters:**
- `per_page` (integer, optional): Default 15, Max 100
- `kategori_id` (integer, optional): Filter by kategori IKU
- `negara_id` (integer, optional): Filter by negara
- `tipe` (string, optional): "dalam_negeri" atau "luar_negeri"
- `search` (string, optional): Search by nama_mitra, email, telepon
- `page` (integer, optional): Halaman untuk pagination

**Example Request:**
```bash
curl -X GET "https://simkerma.app/api/v1/mitra?per_page=10&tipe=luar_negeri&kategori_id=1" \
  -H "X-API-Key: sk_xxxxx"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Data mitra retrieved successfully",
  "data": [
    {
      "id": 1,
      "nama_mitra": "PT. Example Indonesia",
      "kategori_id": 1,
      "negara_id": null,
      "telepon": "021-1234567",
      "email": "info@example.com",
      "alamat": "Jln. Contoh No. 123",
      "qs_rank": null,
      "pic": null,
      "provinsi_id": 1,
      "kota_id": 1,
      "status": 1,
      "created_at": "2026-09-01T10:00:00.000000Z",
      "updated_at": "2026-09-01T10:00:00.000000Z",
      "negara": null,
      "kategori": {
        "id": 1,
        "kategori": "Kategori A",
        "created_at": "2026-08-01T10:00:00.000000Z"
      },
      "provinsiModel": {
        "id": 1,
        "nama_provinsi": "Jawa Barat"
      },
      "kotaModel": {
        "id": 1,
        "nama_kota": "Bandung"
      }
    }
  ],
  "pagination": {
    "total": 150,
    "per_page": 10,
    "current_page": 1,
    "last_page": 15,
    "from": 1,
    "to": 10
  }
}
```

---

### 2. Get Single Mitra
**Endpoint:** `GET /mitra/{id}`

**Path Parameters:**
- `id` (integer, required): ID Mitra

**Example Request:**
```bash
curl -X GET "https://simkerma.app/api/v1/mitra/1" \
  -H "X-API-Key: sk_xxxxx"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Mitra data retrieved successfully",
  "data": {
    "id": 1,
    "nama_mitra": "PT. Example Indonesia",
    "kategori_id": 1,
    "negara_id": null,
    "telepon": "021-1234567",
    "email": "info@example.com",
    "alamat": "Jln. Contoh No. 123",
    "qs_rank": null,
    "pic": null,
    "provinsi_id": 1,
    "kota_id": 1,
    "status": 1,
    "negara": null,
    "kategori": {
      "id": 1,
      "kategori": "Kategori A"
    },
    "provinsiModel": {
      "id": 1,
      "nama_provinsi": "Jawa Barat"
    },
    "kotaModel": {
      "id": 1,
      "nama_kota": "Bandung"
    },
    "kerjasamas": [
      {
        "id": 1,
        "judul": "MoU Collaboration 2026",
        "tanggal_awal": "2026-01-01",
        "tanggal_akhir": "2027-01-01",
        "status": "AKTIF"
      }
    ]
  }
}
```

---

### 3. Get Mitra Kerjasama
**Endpoint:** `GET /mitra/{id}/kerjasama`

**Path Parameters:**
- `id` (integer, required): ID Mitra

**Query Parameters:**
- `per_page` (integer, optional): Default 10

**Example Request:**
```bash
curl -X GET "https://simkerma.app/api/v1/mitra/1/kerjasama?per_page=5" \
  -H "X-API-Key: sk_xxxxx"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Mitra kerjasama data retrieved successfully",
  "mitra": {
    "id": 1,
    "nama_mitra": "PT. Example Indonesia"
  },
  "data": [
    {
      "id": 1,
      "judul": "MoU Collaboration 2026",
      "nomor_dokumen": "123/MoU/2026",
      "tanggal_awal": "2026-01-01",
      "tanggal_akhir": "2027-01-01",
      "status": "AKTIF",
      "jenis_dokumen_id": 1,
      "link_dokumen": "https://example.com/doc.pdf"
    }
  ],
  "pagination": {
    "total": 8,
    "per_page": 5,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

### 4. Get API Key Info
**Endpoint:** `GET /api-key/info`

**Example Request:**
```bash
curl -X GET "https://simkerma.app/api/v1/api-key/info" \
  -H "X-API-Key: sk_xxxxx"
```

**Example Response:**
```json
{
  "success": true,
  "message": "API key info retrieved successfully",
  "data": {
    "name": "Integration Server",
    "is_active": true,
    "last_used_at": "2026-09-01T15:30:00.000000Z",
    "created_at": "2026-09-01T10:00:00.000000Z"
  }
}
```

## Error Responses

### 401 Unauthorized - Missing API Key
```json
{
  "message": "API key is required",
  "error": "missing_api_key"
}
```

### 401 Unauthorized - Invalid API Key
```json
{
  "message": "Invalid or inactive API key",
  "error": "invalid_api_key"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Mitra not found",
  "error": "not_found"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to retrieve mitra data",
  "error": "error message"
}
```

## Rate Limiting & Security

### Best Practices:
1. **Jangan share API Key** - Simpan di environment variables atau secrets management
2. **Rotate Keys Secara Berkala** - Generate key baru dan deactivate yang lama
3. **Monitor Usage** - Check "Last Used" timestamp di halaman API Keys management
4. **Use HTTPS Only** - Pastikan semua request menggunakan HTTPS
5. **IP Whitelisting** - Untuk keamanan lebih, gunakan IP whitelist (feature future)

### Key Management:
- Generate key baru di Admin Panel: Settings → API Keys
- Setiap user memiliki API keys sendiri
- API key tidak pernah ditampilkan kembali setelah di-generate (simpan dengan aman)
- Deactivate key yang tidak digunakan
- Monitor "Last Used" untuk mendeteksi API key yang tidak aktif

## Implementation Example

### Python
```python
import requests

API_KEY = "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
BASE_URL = "https://simkerma.app/api/v1"

headers = {
    "X-API-Key": API_KEY
}

# Get all mitra
response = requests.get(f"{BASE_URL}/mitra", headers=headers)
data = response.json()

if data['success']:
    for mitra in data['data']:
        print(f"{mitra['id']} - {mitra['nama_mitra']}")
```

### JavaScript/Node.js
```javascript
const API_KEY = "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
const BASE_URL = "https://simkerma.app/api/v1";

async function getAllMitra() {
  const response = await fetch(`${BASE_URL}/mitra`, {
    headers: {
      "X-API-Key": API_KEY
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    data.data.forEach(mitra => {
      console.log(`${mitra.id} - ${mitra.nama_mitra}`);
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
])->get('https://simkerma.app/api/v1/mitra');

if ($response->json()['success']) {
    foreach ($response->json()['data'] as $mitra) {
        echo "{$mitra['id']} - {$mitra['nama_mitra']}\n";
    }
}
```

## Support & Troubleshooting

### Q: Bagaimana jika lupa API key?
A: Tidak bisa recover API key yang lama. Generate key baru dan deactivate yang lama.

### Q: Berapa lama API key berlaku?
A: API key tidak memiliki expiry date, tetapi bisa di-deactivate kapan saja.

### Q: Bagaimana tracking penggunaan API key?
A: Lihat "Last Used" timestamp di halaman API Keys management untuk mengetahui kapan terakhir key digunakan.

### Q: Apakah ada rate limiting?
A: Tidak ada rate limiting built-in, tetapi dapat ditambahkan di masa depan jika diperlukan.

## Changelog

### v1.0 (2026-09-01)
- Initial release
- GET /mitra (list dengan pagination)
- GET /mitra/{id} (single mitra with relationships)
- GET /mitra/{id}/kerjasama (kerjasama data)
- GET /api-key/info (API key information)
- API Key authentication via header dan query parameter
