# Aplikasi Mobile SWMS

Aplikasi Flutter warga untuk registrasi/login, verifikasi email, dashboard dan
riwayat laporan, serta pembuatan laporan dengan foto, lokasi GPS, dan deteksi
AI. Kamera laporan mendukung pergantian kamera depan dan belakang; foto juga
dapat dipilih dari galeri. Backend menggunakan Laravel API; service model
FastAPI harus aktif untuk prediksi dan penyimpanan laporan.

## Menjalankan aplikasi

Pasang Flutter SDK dan jalankan dari folder ini:

```powershell
flutter pub get
flutter analyze
flutter test
```

Jalankan Laravel dan FastAPI terlebih dahulu, lalu mulai Flutter Web:

```powershell
flutter run -d chrome --web-port 5000 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Untuk Android Emulator, jalankan Laravel pada host yang dapat dijangkau emulator
dan gunakan `10.0.2.2`:

```powershell
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Konfigurasi CORS Laravel harus menyertakan origin Flutter Web. Android native
tidak menggunakan CORS. Untuk release Android, gunakan endpoint HTTPS:

```powershell
flutter build apk --release --dart-define=API_BASE_URL=https://domain-anda/api/v1
```

APK belum dibuat/disertakan di repository; build Android memerlukan Android
SDK dan Java. Ikuti petunjuk konfigurasi server dan CORS di README utama.
