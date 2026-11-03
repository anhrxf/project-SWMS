# Naskah Video Demo SWMS

Target durasi: **8–9 menit** (batas pengumpulan: 10 menit). Jalankan website,
database, dan FastAPI sebelum merekam. Gunakan data demo lokal atau akun demo
khusus; jangan tampilkan token, password deployment, `.env`, atau data pribadi.

## Persiapan sebelum merekam

1. Pastikan database terisi dengan seeder pada environment lokal.
2. Jalankan FastAPI dari `machine-learning` dengan `python app.py`; cek
   `http://127.0.0.1:8001/health` dan pastikan `model_loaded: true`.
3. Jalankan Laravel dan buka halaman login serta `/api-docs`.
4. Siapkan satu foto sampah yang jelas dan izinkan akses kamera/lokasi bila
   mendemonstrasikan aplikasi pada perangkat.
5. Siapkan `machine-learning/artifacts/evaluation.json` dan
   `confusion_matrix.png`.
6. Tutup terminal atau halaman yang menampilkan rahasia dan notifikasi pribadi.

## Alur dan narasi

| Waktu | Demonstrasi | Narasi yang disarankan |
|---|---|---|
| 0:00–0:35 | Tampilkan repository dan struktur komponen | “SWMS terdiri dari website Laravel, REST API, aplikasi Flutter warga, dan service klasifikasi FastAPI berbasis PyTorch.” |
| 0:35–1:15 | Login sebagai admin dan buka dashboard | “Dashboard merangkum laporan dan status. Hak admin divalidasi di server, bukan hanya disembunyikan di antarmuka.” |
| 1:15–2:05 | Filter/cari laporan, buka detail, ubah status dengan catatan | “Admin dapat mencari laporan, meninjau foto dan lokasi, memperbarui status, dan menyimpan jejak perubahan.” |
| 2:05–2:40 | Keluar, login sebagai warga | “Warga melihat laporan miliknya; akses laporan pengguna lain dibatasi.” |
| 2:40–3:35 | Buat laporan web: pilih foto, tunjukkan deteksi AI dan lokasi | “Foto dikirim untuk klasifikasi. Jika AI ragu, warga tetap dapat memilih kategori secara manual.” |
| 3:35–4:35 | Tunjukkan aplikasi mobile: dashboard, riwayat, kamera depan/belakang, ambil foto, GPS | “Aplikasi Flutter memakai API Laravel. Pengguna dapat berganti kamera, memilih foto, mengambil lokasi, dan mengirim laporan.” |
| 4:35–5:20 | Tampilkan laporan/status di aplikasi mobile | “Status laporan dan riwayat diperoleh dari API sesuai akun yang sedang masuk.” |
| 5:20–6:00 | Tampilkan `/api-docs` dan dokumen API | “API mendokumentasikan autentikasi, laporan multipart, klasifikasi, status, dan format respons.” |
| 6:00–7:10 | Tampilkan service `/health`, lalu klasifikasikan gambar | “FastAPI memuat model saat startup. Prediksi hanya menetapkan kelas jika ambang confidence dan pemeriksaan konsistensi terpenuhi.” |
| 7:10–8:00 | Tampilkan dataset, atribusi, metrik, confusion matrix | “Dataset berisi 150 foto seimbang. Akurasi test 91,30% berasal dari 23 foto holdout; ukuran test kecil sehingga bukan jaminan akurasi di dunia nyata.” |
| 8:00–8:35 | Tampilkan ERD dan tes otomatis | “ERD mendokumentasikan relasi utama. Validasi proyek mencakup tes Laravel, Flutter, dan FastAPI.” |
| 8:35–8:50 | Penutup | “Source code, dokumentasi, model, dataset, dan evaluasi tersedia di repository. Tautan demo dan APK dicantumkan bersama video saat sudah dipublikasikan.” |

## Pemeriksaan hasil rekaman

- [ ] Durasi video kurang dari atau sama dengan 10 menit.
- [ ] Teks dan antarmuka terbaca; narasi menjelaskan alur, bukan hanya berpindah halaman.
- [ ] Tidak ada token API, kredensial production, isi `.env`, alamat pribadi, atau
  pesan email pribadi yang terlihat.
- [ ] Klaim evaluasi menyebutkan 23 sampel test dan keterbatasan dataset.
- [ ] Video diunggah dengan akses yang dapat dibuka reviewer; tautan dicatat di
  [`SUBMISSION_CHECKLIST.md`](./SUBMISSION_CHECKLIST.md).
