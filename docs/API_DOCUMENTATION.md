# Dokumentasi Lengkap REST API - Smart Waste Management System (SWMS)

Versi: **v1.0.0**  
Base URL: `http://localhost:8000/api/v1`  
Format Respons: `application/json`  
Skema Autentikasi: `Bearer <token>` (Laravel Sanctum)

---

## 1. Autentikasi Pengguna

### A. Register Akun Baru
- **Endpoint**: `POST /api/v1/auth/register`
- **Akses**: Publik
- **Headers**: `Content-Type: application/json`, `Accept: application/json`

#### Request Body
```json
{
  "name": "Budi Santoso",
  "email": "budi.santoso@email.com",
  "phone": "081234567890",
  "address": "Jl. Merdeka No. 45, Jakarta",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Response Success (201 Created)
```json
{
  "success": true,
  "message": "Registrasi berhasil.",
  "data": {
    "user": {
      "id": 4,
      "name": "Budi Santoso",
      "email": "budi.santoso@email.com",
      "role": "user",
      "phone": "081234567890",
      "address": "Jl. Merdeka No. 45, Jakarta"
    },
    "token": "1|w7r9VwQ62D...sanctum_token...",
    "token_type": "Bearer"
  }
}
```

---

### B. Login Akun
- **Endpoint**: `POST /api/v1/auth/login`
- **Akses**: Publik

#### Request Body
```json
{
  "email": "user@swms.com",
  "password": "password",
  "device_name": "Mobile_Flutter"
}
```

#### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": 2,
      "name": "Budi Santoso",
      "email": "user@swms.com",
      "role": "user",
      "phone": "081298765432",
      "address": "Jl. Kebon Jeruk Raya No. 12, Jakarta Barat"
    },
    "token": "2|eR7u91xZ...token...",
    "token_type": "Bearer"
  }
}
```

---

### C. Profil Pengguna
- **Endpoint**: `GET /api/v1/auth/profile`
- **Akses**: Terotentikasi (`Bearer <token>`)

#### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Budi Santoso",
    "email": "user@swms.com",
    "role": "user",
    "phone": "081298765432",
    "address": "Jl. Kebon Jeruk Raya No. 12, Jakarta Barat",
    "created_at": "2026-11-01T10:30:00.000000Z"
  }
}
```

---

### D. Logout
- **Endpoint**: `POST /api/v1/auth/logout`
- **Akses**: Terotentikasi (`Bearer <token>`)

#### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Berhasil logout dan token dicabut."
}
```

---

## 2. Fitur AI Image Classification

### Deteksi Kategori Sampah Gambar
- **Endpoint**: `POST /api/v1/ai/classify`
- **Akses**: Publik / Terotentikasi
- **Content-Type**: `multipart/form-data`

#### Parameter Form-Data
- `image`: File berkas gambar (JPG, PNG, WEBP - Maksimal 10MB)

#### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Analisis gambar berhasil dilakukan.",
  "data": {
    "source": "builtin_smart_classifier",
    "category": "plastic",
    "confidence": 95.4,
    "label": "Sampah Plastik (Plastic Waste)",
    "breakdown": {
      "organic": 2.3,
      "plastic": 95.4,
      "paper": 2.3
    },
    "recommendation": {
      "type": "Daur Ulang Plastik / Bank Sampah",
      "action": "Bilas hingga bersih dari sisa cairan atau minyak, remukkan botol untuk menghemat ruang, dan kumpulkan ke Bank Sampah terdekat.",
      "color_code": "#3b82f6",
      "bin": "Tempat Sampah Kuning (Anorganik / Plastik)"
    }
  }
}
```

---

## 3. Manajemen Laporan Sampah

### A. Mendapatkan Daftar Laporan
- **Endpoint**: `GET /api/v1/reports`
- **Akses**: Terotentikasi (`Bearer <token>`)
- **Query Parameters**:
  - `status`: `pending` | `in_progress` | `completed` | `rejected`
  - `category`: `organic` | `plastic` | `paper` | `other`
  - `search`: Kata kunci pencarian (judul / lokasi / nama)
  - `page`: Nomor halaman paginasi (default: 1)
  - `per_page`: Jumlah data per halaman (default: 15)

#### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "title": "Timbunan Botol Plastik dan Gelas Mineral di Dekat Halte",
        "description": "Banyak botol plastik sekali pakai dan gelas air mineral berserakan di sekitar halte busway.",
        "waste_category": "plastic",
        "ai_detected_category": "plastic",
        "ai_confidence": 95.4,
        "image_path": "reports/plastic_bottles.jpg",
        "latitude": -6.175392,
        "longitude": 106.827153,
        "location_address": "Jl. Medan Merdeka Barat, Halte Monas, Gambir",
        "status": "completed",
        "admin_notes": "Armada 01 Dinas Lingkungan Hidup telah mengangkut sampah plastik.",
        "resolved_at": "2026-10-28T09:00:00.000000Z",
        "created_at": "2026-10-27T08:00:00.000000Z",
        "user": {
          "id": 2,
          "name": "Budi Santoso",
          "email": "user@swms.com"
        }
      }
    ],
    "total": 7,
    "per_page": 15
  }
}
```

---

### B. Membuat Laporan Sampah Baru
- **Endpoint**: `POST /api/v1/reports`
- **Akses**: Terotentikasi (`Bearer <token>`)
- **Content-Type**: `multipart/form-data`

#### Parameter Form-Data
- `title` (string, required): Judul ringkas laporan
- `description` (string, required): Keterangan kondisi sampah
- `waste_category` (string, required): `organic`, `plastic`, `paper`, atau `other`
- `image` (file, required): File foto sampah (JPG, PNG, WEBP)
- `latitude` (numeric, optional): Koordinat lintang GPS (cth: `-6.2088`)
- `longitude` (numeric, optional): Koordinat bujur GPS (cth: `106.8456`)
- `location_address` (string, optional): Alamat patokan fisik
- `ai_detected_category` (string, optional): Hasil deteksi AI jika diproses sebelum submit
- `ai_confidence` (numeric, optional): Persentase keyakinan AI

#### Response Success (201 Created)
```json
{
  "success": true,
  "message": "Laporan berhasil dibuat.",
  "data": {
    "id": 8,
    "user_id": 2,
    "title": "Timbunan Sampah Plastik Pinggir Selokan",
    "description": "Botol plastik menumpuk di saluran air depan ruko.",
    "waste_category": "plastic",
    "ai_detected_category": "plastic",
    "ai_confidence": 94.2,
    "image_path": "reports/92f8a...jpg",
    "status": "pending",
    "latitude": -6.200000,
    "longitude": 106.816600,
    "location_address": "Jl. Sudirman No. 12",
    "created_at": "2026-11-01T11:00:00.000000Z"
  }
}
```

---

### C. Detail Laporan Sampah
- **Endpoint**: `GET /api/v1/reports/{id}`
- **Akses**: Terotentikasi (`Bearer <token>`)

#### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "report": {
      "id": 1,
      "title": "Timbunan Botol Plastik dan Gelas Mineral di Dekat Halte",
      "waste_category": "plastic",
      "status": "completed",
      "status_logs": [
        {
          "id": 1,
          "report_id": 1,
          "previous_status": null,
          "new_status": "pending",
          "notes": "Laporan awal dibuat oleh pelapor."
        },
        {
          "id": 2,
          "report_id": 1,
          "previous_status": "pending",
          "new_status": "completed",
          "notes": "Armada telah membersihkan lokasi."
        }
      ]
    },
    "image_url": "http://localhost:8000/storage/reports/plastic_bottles.jpg",
    "recommendation": {
      "type": "Daur Ulang Plastik / Bank Sampah",
      "action": "Bilas bersih dan daur ulang ke bank sampah.",
      "bin": "Tempat Sampah Kuning (Plastik)"
    }
  }
}
```

---

### D. Update Status Laporan (Khusus Administrator)
- **Endpoint**: `POST /api/v1/reports/{id}/status`
- **Akses**: Terotentikasi Role Admin (`Bearer <admin_token>`)
- **Content-Type**: `application/json`

#### Request Body
```json
{
  "status": "completed",
  "admin_notes": "Petugas kebersihan telah mengangkut sampah dan membersihkan area trotoar."
}
```

#### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Status laporan berhasil diperbarui.",
  "data": {
    "id": 1,
    "status": "completed",
    "admin_notes": "Petugas kebersihan telah mengangkut sampah dan membersihkan area trotoar.",
    "resolved_at": "2026-11-01T11:15:00.000000Z"
  }
}
```
