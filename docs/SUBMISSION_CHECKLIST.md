# Checklist Pengumpulan Technical Coding Test

Status ini menggambarkan berkas lokal yang tersedia saat diperiksa. Tautan demo,
APK, video, dan publikasi perubahan ke GitHub harus dilengkapi sebelum
pengumpulan.

## Status deliverable

| Deliverable | Status | Bukti / tindak lanjut |
|---|---|---|
| Repository GitHub dan source code | Sebagian siap | Repository remote: <https://github.com/anhrxf/project-SWMS>. Perubahan lokal yang belum di-commit dan di-push harus ditinjau sebelum pengumpulan. |
| Website demo | Belum siap | Belum ada URL deployment. Deploy Laravel, database, storage, dan service AI; lalu catat URL publik di formulir pengumpulan. |
| APK aplikasi mobile | Belum siap | Belum ada APK di `mobile/build`. Build release setelah URL API production HTTPS tersedia dan uji instalasi pada perangkat Android. |
| Dokumentasi API | Siap di repository | [`API_DOCUMENTATION.md`](./API_DOCUMENTATION.md); dokumentasi web lokal tersedia di `/api-docs`. |
| Skema database / ERD | Siap di repository | [`ERD.md`](./ERD.md), mencakup tabel pengguna, laporan, log status, dan token API. |
| Source code dan model ML | Siap di repository | `machine-learning/classifier.py`, `fastapi_app.py`, `train.py`, dan `machine-learning/artifacts/model.pt`. |
| Dataset dan sumber | Siap di repository | 150 foto (50 per kelas), atribusi dan SHA-256 di `machine-learning/training_photos/attributions.csv`; sumber dan lisensi Apache-2.0 dijelaskan di README dataset. |
| Hasil evaluasi model | Siap di repository | `machine-learning/artifacts/evaluation.json`, `confusion_matrix.png`, `test_predictions.csv`, dan `training_history.json`. Akurasi test tercatat 91,30% pada 23 foto. |
| Panduan instalasi dan penggunaan | Siap di repository | [`README.md`](../README.md), [`mobile/README.md`](../mobile/README.md), dan [`machine-learning/README.md`](../machine-learning/README.md). |
| Video demo maksimal 10 menit | Belum direkam | Gunakan [`VIDEO_DEMO_SCRIPT.md`](./VIDEO_DEMO_SCRIPT.md); rekam maksimal 9 menit, unggah ke layanan yang dapat dibuka reviewer, lalu catat tautannya. |

## Pemeriksaan sebelum publikasi

- [ ] Tinjau perubahan lokal dan commit hanya berkas source/docs yang memang akan dikumpulkan.
- [ ] Push commit akhir ke repository GitHub dan pastikan repository dapat diakses reviewer.
- [ ] Deploy website dan FastAPI; pastikan `/health` memberi status online dan klasifikasi berfungsi.
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, database privat, dan mailer produksi.
- [ ] Konfigurasikan URL API HTTPS untuk aplikasi mobile; jangan rilis APK dengan URL localhost.
- [ ] Build dan uji APK pada perangkat Android, termasuk kamera, flip kamera, GPS, galeri, login, dan pengiriman laporan.
- [ ] Jalankan `php artisan test`, `cd mobile; flutter analyze; flutter test`, serta `cd machine-learning; python -m unittest test_fastapi_app -v`.
- [ ] Rekam alur video sesuai naskah; pastikan durasi tidak lebih dari 10 menit dan jangan tampilkan kredensial atau rahasia.
- [ ] Isi tautan final pengumpulan:

| Tautan | Nilai |
|---|---|
| Repository GitHub | <https://github.com/anhrxf/project-SWMS> |
| Demo website | Belum tersedia |
| APK / halaman unduh | Belum tersedia |
| Video demo | Belum tersedia |

## Catatan demo lokal

Seeder akun demo hanya bekerja pada `APP_ENV=local` atau `testing`. Akun demo
yang didokumentasikan di README hanya untuk penggunaan lokal; jangan gunakan
kredensial tersebut pada deployment publik. Untuk demo hosted, buat akun demo
terpisah dengan akses terbatas dan kredensial unik.
