# Dokumentasi REST API SWMS

Versi: `v1`

Base URL lokal: `http://localhost:8000/api/v1`
Format respons: JSON

## Autentikasi dan format request

Endpoint terautentikasi menggunakan Laravel Sanctum personal access token. Kirim token pada setiap request terautentikasi:

```http
Authorization: Bearer &lt;token&gt;
Accept: application/json
```

Token dikembalikan oleh register dan login. Gunakan `application/json` untuk request biasa. Untuk request yang mengunggah gambar, gunakan `multipart/form-data` dan biarkan HTTP client menentukan boundary `Content-Type`.

## Endpoint autentikasi

### Register

- `POST /auth/register` — publik; berhasil `201 Created`.
- Email wajib valid dan unik.
- Nomor HP wajib, unik, dan memiliki panjang 9–20 karakter.
- `address` opsional.
- Aplikasi mengirim tautan verifikasi dengan masa berlaku 60 menit.
- Respons menyertakan token akses, `email_verified: false`, dan `verification_url`.
- Tautan dapat dibuka tanpa login karena URL bertanda tangan dan kedaluwarsa.
- Request API laporan ditolak dengan HTTP 403 sampai email diverifikasi.

Request JSON:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "phone": "081234567890",
  "address": "Jl. Merdeka No. 45",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Respons mengandung `success`, `message`, serta `data.user`, `data.token`, `data.token_type` (`Bearer`), `data.email_verified`, dan `data.verification_url`.

### Login

- `POST /auth/login` — publik; berhasil `200 OK`.
- `device_name` opsional; default `swms_mobile_app`.

Request JSON:

```json
{
  "email": "budi@example.com",
  "password": "password123",
  "device_name": "Flutter_Android"
}
```

Respons mengandung `success`, `message`, serta `data.user`, `data.token`, `data.token_type`, dan `data.email_verified`. Untuk akun belum diverifikasi, login tetap menghasilkan token agar klien dapat mengambil profil atau meminta tautan baru; akses ke API laporan tetap ditolak.
Kredensial tidak cocok menghasilkan `401 Unauthorized`.

### Profil

- `GET /auth/profile` — terautentikasi; `200 OK`.
- Data profil ada di `data`: `id`, `name`, `email`, `role`, `phone`, `address`, `email_verified`, `email_verified_at`, dan `created_at`.

### Logout

- `POST /auth/logout` — terautentikasi; `200 OK`.
- Mencabut token yang digunakan pada request tersebut.

### Kirim ulang verifikasi email

- `POST /auth/email/verification-notification` — terautentikasi; dibatasi enam request per menit.
- Mengirim notifikasi verifikasi baru untuk akun belum terverifikasi; sukses `202 Accepted`.
- Jika sudah terverifikasi, sukses `200 OK` dengan pesan bahwa alamat telah diverifikasi.

Link verifikasi dikirim melalui email dan juga tersedia sebagai `verification_url` pada respons register. Link berlaku 60 menit dan memiliki signature. Buka URL tersebut di browser; login tidak diperlukan.

Mailer lokal pada `.env.example` menggunakan driver `log`; email verifikasi ditulis ke
`storage/logs/laravel.log`, bukan dikirim ke inbox. Atur SMTP dan `APP_URL` publik untuk
pengiriman nyata. Untuk Gmail, gunakan App Password dengan verifikasi dua langkah aktif,
bukan kata sandi akun Google.

## Klasifikasi gambar

### `POST /ai/classify`

Publik, tidak memerlukan token. Request `multipart/form-data` berisi field wajib `image` (JPEG, PNG, JPG, atau WEBP; maksimum 10 MB).

Berhasil menghasilkan `200 OK` dengan `success`, `message`, dan `data`. Data berisi `source`, `category`, `confidence`, `label`, `breakdown`, dan `recommendation` (`type`, `action`, `color_code`, `bin`). Kategori yang dikembalikan classifier: `organic`, `plastic`, atau `paper`.

## Laporan

### Daftar laporan — `GET /reports`

Terautentikasi dan email harus sudah diverifikasi. Admin melihat seluruh laporan; warga hanya melihat laporan miliknya. Jika belum diverifikasi, API merespons `403 Forbidden`.

Query opsional:

| Parameter | Nilai / arti |
|---|---|
| `status` | `pending`, `in_progress`, `completed`, `rejected` |
| `category` | `organic`, `plastic`, `paper`, `other` |
| `search` | Mencari pada judul, deskripsi, dan alamat lokasi |
| `page` | Nomor halaman |
| `per_page` | Jumlah item per halaman; default `15` |

Respons membungkus objek paginator Laravel di dalam `data`; objek tersebut mencakup `current_page`, daftar `data`, `total`, dan `per_page`.

### Membuat laporan — `POST /reports`

Terautentikasi sebagai warga dengan email terverifikasi; akun admin atau akun belum terverifikasi menerima `403 Forbidden`. Request `multipart/form-data`.

| Field | Validasi |
|---|---|
| `title` | Wajib, string, maksimum 255 karakter |
| `description` | Wajib, string |
| `waste_category` | Wajib: `organic`, `plastic`, `paper`, atau `other` |
| `image` | Wajib; JPEG, PNG, JPG, atau WEBP; maksimum 10 MB |
| `latitude` | Opsional, numerik antara -90 dan 90 |
| `longitude` | Opsional, numerik antara -180 dan 180 |
| `location_address` | Opsional, string, maksimum 500 karakter |
| `ai_detected_category` | Opsional, string |
| `ai_confidence` | Opsional, numerik |

Sukses menghasilkan `201 Created` dengan laporan pada `data`, termasuk relasi `user` dan `status_logs`. Status awal laporan adalah `pending`.

### Detail laporan — `GET /reports/{report}`

Terautentikasi dengan email terverifikasi. Admin dapat melihat semua laporan; warga hanya dapat melihat laporan miliknya. Respons sukses `200 OK`:

- `data.report`: detail laporan beserta relasi pengguna dan log status.
- `data.image_url`: URL gambar.
- `data.recommendation`: rekomendasi berdasarkan kategori sampah.

Laporan tidak ditemukan menghasilkan `404 Not Found`; warga yang mencoba melihat laporan milik pengguna lain menerima `403 Forbidden`.

### Memperbarui status — `POST /reports/{report}/status`

Khusus admin dengan email terverifikasi. Request JSON:

```json
{
  "status": "completed",
  "admin_notes": "Sampah telah diangkut."
}
```

- `status` wajib: `pending`, `in_progress`, `completed`, atau `rejected`.
- `admin_notes` opsional, string maksimum 1.000 karakter.
- Tidak ada urutan transisi yang diwajibkan oleh endpoint.
- Sukses menghasilkan `200 OK`, memperbarui `data` laporan, dan menambahkan entri riwayat status.
- Akun non-admin menerima `403 Forbidden`.

## Respons error umum

| HTTP | Arti |
|---|---|
| `401` | Token tidak ada atau tidak valid. |
| `403` | Akses ditolak karena role atau kepemilikan laporan. |
| `404` | Laporan tidak ditemukan. |
| `422` | Validasi gagal; Laravel mengembalikan `message` dan detail per-field di `errors`. |

Request API harus menyertakan `Accept: application/json` agar error validasi dikembalikan sebagai JSON.
