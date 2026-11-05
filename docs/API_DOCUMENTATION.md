# Dokumentasi REST API SWMS

Versi: `v1`

Base URL lokal: `http://localhost:8000/api/v1`
Format respons: JSON

Untuk klien Flutter Web, konfigurasi origin web secara eksplisit di
`SWMS_CORS_ALLOWED_ORIGINS` pada `.env`. Nilai default mengizinkan origin lokal
`localhost`/`127.0.0.1` pada port `5000` dan `8080`. Untuk deployment, tambahkan origin
HTTPS aplikasi web yang tepat; jangan memakai wildcard. Klien Android native tidak
terpengaruh oleh CORS.

## Autentikasi dan format request

Endpoint terautentikasi menggunakan Laravel Sanctum personal access token. Kirim token pada setiap request terautentikasi:

```http
Authorization: Bearer &lt;token&gt;
Accept: application/json
```

Token dikembalikan oleh register dan login. Gunakan `application/json` untuk request biasa. Untuk request yang mengunggah gambar, gunakan `multipart/form-data` dan biarkan HTTP client menentukan boundary `Content-Type`.

### Kredensial default untuk pengujian API

| Peran | Email | Kata Sandi | Keterangan |
|---|---|---|---|
| **Administrator** | `swms.admin@gmail.com` | `admin.swms2026` | Hak akses penuh: manajemen laporan semua warga, update status laporan (`POST /reports/{report}/status`) |
| **Warga (User)** | `user@swms.com` | `password` | Pengguna biasa: kirim laporan sampah, lihat riwayat laporan sendiri |

## Endpoint autentikasi

### Register

- `POST /auth/register` — publik; berhasil `201 Created`.
- Email wajib valid dan unik.
- Nomor HP wajib, unik, dan memiliki panjang 9–20 karakter.
- `address` opsional.
- Jika verifikasi email aktif, aplikasi mengirim tautan verifikasi dengan masa berlaku 5 menit.
- Respons menyertakan token akses dan `email_verified: false`; tautan verifikasi hanya dikirim melalui email.
- Tautan dapat dibuka tanpa login karena URL bertanda tangan dan kedaluwarsa.
- Jika verifikasi email aktif, request API laporan ditolak dengan HTTP 403 sampai email diverifikasi.

Request JSON:

```json
{
  "name": "Budi Santoso",
  "email": "budi@gmail.com",
  "phone": "081234567890",
  "address": "Jl. Merdeka No. 45",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Respons mengandung `success`, `message`, `email_sent`, `verification_expires_at`, serta `data.user`, `data.token`, `data.token_type` (`Bearer`), dan `data.email_verified`. API tidak mengembalikan URL bertanda tangan untuk mencegah verifikasi email oleh pihak yang tidak menguasai inbox. Tautan berlaku 5 menit; `verification_expires_at` adalah waktu kedaluwarsa dalam ISO 8601. Jika `MAIL_MAILER=log`, `email_sent` bernilai `false`: pesan hanya ditulis ke log dan tidak masuk inbox. Respons register tetap berhasil karena akun sudah dibuat; atur SMTP lalu minta tautan baru.

### Login

- `POST /auth/login` — publik; berhasil `200 OK`.
- `device_name` opsional; default `swms_mobile_app`.

Request JSON:

```json
{
  "email": "budi@gmail.com",
  "password": "password123",
  "device_name": "Flutter_Android"
}
```

Respons mengandung `success`, `message`, serta `data.user`, `data.token`, `data.token_type`, dan `data.email_verified`. Untuk akun belum diverifikasi, login tetap menghasilkan token agar klien dapat mengambil profil atau meminta tautan baru; akses ke API laporan tetap ditolak.
Kredensial tidak cocok menghasilkan `401 Unauthorized` dengan pesan `Alamat email salah.`.

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
- Jika mailer masih `log`, respons `503 Service Unavailable` dengan `email_sent: false`; email hanya masuk ke `storage/logs/laravel.log`.

Link verifikasi bertanda tangan hanya dikirim melalui email, berlaku 5 menit, dan dapat dibuka di browser tanpa login. API register tidak mengembalikan link tersebut. Endpoint kirim ulang mengembalikan `verification_expires_at` untuk waktu kedaluwarsa link terbaru.

Mailer lokal pada `.env.example` menggunakan driver `log`; email verifikasi ditulis ke
`storage/logs/laravel.log`, bukan dikirim ke inbox. Atur SMTP dan `APP_URL` publik untuk
pengiriman nyata. Verifikasi email aktif secara default di luar environment `local`
dan `testing`; atur `EMAIL_VERIFICATION_ENABLED` secara eksplisit pada deployment
production. Untuk Gmail, gunakan App Password dengan verifikasi dua langkah aktif,
bukan kata sandi akun Google.

## Klasifikasi gambar

### `POST /ai/classify`

Publik, tidak memerlukan token. Request `multipart/form-data` berisi field wajib `image` (JPEG, PNG, JPG, atau WEBP; maksimum 10 MB).

Berhasil diproses menghasilkan `200 OK` dengan `success`, `message`, dan `data`.
Kategori hanya ditetapkan bila skor keluaran model minimal 70% dan prediksi pada
foto asli serta foto yang dibalik horizontal sama. Jika tidak, respons tetap `200`,
`data.status` bernilai `uncertain`, `data.category` dan `data.recommendation` bernilai
`null`; jangan tampilkan skor kelas sebagai hasil pasti. Model hanya membedakan
organic, plastic, dan paper, sehingga bukan pemeriksa umum untuk semua objek.
Jika layanan/model AI tidak tersedia, endpoint mengembalikan `503 Service
Unavailable`; gambar yang tidak dapat diproses mengembalikan `422 Unprocessable
Content`.

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

Pada aplikasi mobile, foto dapat diambil dengan kamera depan atau belakang
menggunakan tombol balik kamera, atau dipilih dari galeri. Pemilihan kamera
sepenuhnya ditangani klien dan tidak mengubah kontrak API: server menerima file
foto hasilnya melalui field `image` tanpa field tambahan untuk jenis kamera.

| Field | Validasi |
|---|---|
| `title` | Wajib, string, maksimum 255 karakter |
| `description` | Wajib, string |
| `waste_category` | Wajib: `organic`, `plastic`, `paper`, atau `other` |
| `image` | Wajib; JPEG, PNG, JPG, atau WEBP; maksimum 10 MB |
| `latitude` | Opsional, numerik antara -90 dan 90 |
| `longitude` | Opsional, numerik antara -180 dan 180 |
| `location_address` | Opsional, string, maksimum 500 karakter |

Contoh request:

```bash
curl -X POST "$API_BASE_URL/reports" \
  -H "Authorization: $API_AUTHORIZATION" \
  -H "Accept: application/json" \
  -F "title=Tumpukan sampah di taman" \
  -F "description=Sampah menumpuk dekat pintu masuk taman." \
  -F "waste_category=organic" \
  -F "image=@foto-laporan.jpg;type=image/jpeg" \
  -F "latitude=-6.200000" \
  -F "longitude=106.816666" \
  -F "location_address=Jl. Merdeka"
```

Isi `API_AUTHORIZATION` dengan skema `Bearer` diikuti token akses dari register
atau login.

Jangan set header `Content-Type` secara manual; HTTP client harus menambahkan
boundary `multipart/form-data`. Berhasil menghasilkan `201 Created` dengan
`success`, `message`, `ai_prediction_status` (`classified` atau `uncertain`),
dan `data` berisi laporan yang dibuat. Kategori hasil AI yang ragu tidak
menghalangi pembuatan laporan; kategori yang disimpan tetap pilihan pada
`waste_category`.

Kategori AI selalu dihitung ulang oleh server dari file yang diunggah; nilai
prediksi yang dikirim klien tidak digunakan. Jika AI yakin, kategori dan confidence
disimpan. Jika AI ragu, laporan tetap dapat dibuat dengan `waste_category` pilihan
pengguna, sedangkan `ai_detected_category` dan `ai_confidence` tetap `null`.
Kegagalan layanan/model tetap menghasilkan `503` dan laporan tidak disimpan.

### Detail laporan — `GET /reports/{report}`

Terautentikasi dengan email terverifikasi. Admin dapat melihat semua laporan; warga hanya dapat melihat laporan miliknya. Respons sukses `200 OK`:

- `data.report`: detail laporan beserta relasi pengguna dan log status.
- `data.image_url`: URL gambar bertanda tangan yang berlaku selama 30 menit.
- `data.recommendation`: rekomendasi berdasarkan kategori sampah.

URL gambar pada laporan API (`image_url`) juga bersifat sementara. Ambil URL
terbaru dari endpoint laporan sebelum menampilkan gambar; jangan menyimpan URL
tersebut sebagai alamat permanen.

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
| `503` | Layanan klasifikasi AI tidak tersedia atau mengembalikan respons yang tidak dapat digunakan. |

Request API harus menyertakan `Accept: application/json` agar error validasi dikembalikan sebagai JSON.
