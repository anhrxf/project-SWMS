# Naskah Lengkap Video Demo SWMS (Smart Waste Management System)

Target durasi: **7–9 menit** (Batas maksimal tes: 10 menit).  
Dokumen ini memuat panduan demonstrasi dan **naskah kata-per-kata (word-for-word script)** yang siap Anda bacakan saat merekam video demo pengumpulan hasil Technical Coding Test.

---

## 1. Persiapan Sebelum Mulai Merekam

Buka tab-tab berikut di browser Anda sebelum menekan tombol *Record*:
1. **Tab 1:** Halaman Login Website (`http://127.0.0.1:8000/login` atau link tunnel publik).
2. **Tab 2:** Halaman Demo Mobile Web (`http://127.0.0.1:8000/mobile/` atau buka emulator/perangkat).
3. **Tab 3:** Halaman AI Sandbox & Evaluasi (`/ai-lab`).
4. **Tab 4:** Halaman Dokumentasi REST API (`/api-docs`).
5. **Tab 5:** File `machine-learning/artifacts/confusion_matrix.png` atau file `docs/ERD.md` di VS Code.
6. **Kredensial yang digunakan:**
   - **Administrator:** `swms.admin@gmail.com` | Sandi: `admin.swms2026`
   - **Warga (User):** `user@swms.com` | Sandi: `password`

---

## 2. Tabel Garis Besar Timeline Video

| Waktu | Segmen | Layar yang Ditampilkan |
|---|---|---|
| **00:00 – 00:45** | Pembukaan & Pengenalan Arsitektur | VS Code / Repository GitHub SWMS |
| **00:45 – 02:00** | Website: Role Administrator & Manajemen Laporan | Dashboard Admin, Tabel Laporan, Update Status, Manajemen Pengguna |
| **02:00 – 03:30** | Website: Role Warga & Pembuatan Laporan Berbasis AI | Login Warga, Form Buat Laporan, Deteksi AI & GPS Peta |
| **03:30 – 05:00** | Aplikasi Mobile Flutter (User Experience) | Aplikasi Mobile: Login, Dashboard, Buat Laporan Kamera/Galeri, Live AI |
| **05:00 – 06:15** | Machine Learning & Deteksi Sampah Campuran | FastAPI `/health`, Klasifikasi Gambar Murni & Sampah Campuran |
| **06:15 – 07:15** | Dataset, Metrik Evaluasi & Confusion Matrix | Confusion Matrix, Metrics `evaluation.json`, Penjelasan Model |
| **07:15 – 08:00** | Dokumentasi REST API & ERD Database | Halaman `/api-docs` & Skema ERD Database |
| **08:00 – 08:30** | Pengujian Otomatis & Penutup | Terminal Test (`php artisan test`, `flutter test`), Penutup |

---

## 3. Naskah Kata-per-Kata (Spoken Word-for-Word Script)

Bacakan narasi berikut secara tenang, runtut, dan jelas sembari menggerakkan kursor mouse ke elemen yang sedang dijelaskan:

---

### Bagian 1: Pembukaan & Gambaran Sistem (00:00 – 00:45)
> **Layar:** Buka repository GitHub atau VS Code yang menampilkan struktur folder proyek (`app`, `mobile`, `machine-learning`, `docs`).

**Kata-kata yang diucapkan:**
> *"Halo, perkenalkan nama saya [Sebutkan Nama Anda]. Pada video ini, saya akan mendemonstrasikan hasil pengerjaan Technical Coding Test untuk posisi Fullstack Developer di Bilcode Technology, yaitu **Smart Waste Management System (SWMS)**.*
>
> *Sistem ini dibangun dengan arsitektur modern yang mengintegrasikan tiga komponen utama:*
> 1. *Pertama, **Website Development** berbasis Laravel 11 dengan Tailwind CSS dan Alpine.js.*
> 2. *Kedua, **Mobile Application** berbasis Flutter yang dirancang responsif dan terintegrasi penuh ke backend.*
> 3. *Ketiga, **Machine Learning Microservice** menggunakan Python, PyTorch MobileNetV3, dan FastAPI untuk klasifikasi citra sampah secara otomatis.*
>
> *Mari kita mulai dengan mendemonstrasikan website pada role Administrator."*

---

### Bagian 2: Website - Role Administrator (00:45 – 02:00)
> **Layar:** Buka halaman login web (`/login`), masukkan `swms.admin@gmail.com` dan `admin.swms2026`, lalu klik **Masuk ke Sistem**.

**Kata-kata yang diucapkan:**
> *"Di sini kita berada di halaman login. Kita masuk menggunakan akun Administrator: `swms.admin@gmail.com`.*
>
> *Setelah berhasil masuk, kita disambut oleh **Dashboard Statistik Wilayah**. Di bagian atas terdapat kartu metrik ringkasan: Total Laporan, Laporan Menunggu, Diproses, dan Selesai. Di bawahnya terdapat grafik distribusi kategori sampah dan tren pelaporan.*
>
> *Sekarang kita buka menu **Daftar Laporan**. Admin memiliki akses pengawasan penuh ke seluruh laporan dari semua warga. Terdapat fitur pencarian berbasis judul, deskripsi, atau alamat, serta filter berdasarkan status dan kategori sampah.*
>
> *Mari kita buka salah satu laporan yang berstatus 'Pending'. Di halaman detail ini, Admin dapat melihat foto bukti sampah, hasil deteksi AI, koordinat GPS peta, serta riwayat penanganan.*
>
> *Admin dapat memperbarui status laporan, misalnya kita ubah menjadi **'Diproses'** atau **'Selesai'**, dan menambahkan catatan tindak lanjut petugas: 'Armada kebersihan telah mengangkut sampah ke TPS3R'. Status berhasil diperbarui dan tercatat lengkap di riwayat log.*
>
> *Selain itu, Admin memiliki menu **Manajemen Pengguna** untuk memantau data warga dan staf yang terdaftar di sistem. Akses ke menu ini diproteksi ketat menggunakan middleware berbasis role di tingkat server."*

---

### Bagian 3: Website - Role Warga & Pembuatan Laporan Berbasis AI (02:00 – 03:30)
> **Layar:** Klik menu profil di pojok kanan atas $\rightarrow$ **Keluar Akun**. Lalu login menggunakan akun warga: `user@swms.com` / `password`. Buka halaman **Buat Laporan** (`/reports/create`).

**Kata-kata yang diucapkan:**
> *"Sekarang kita keluar dan masuk sebagai pengguna warga menggunakan akun `user@swms.com`.*
>
> *Berbeda dengan Admin, warga hanya dapat melihat dan mengelola laporan miliknya sendiri demi privasi data. Mari kita buat laporan baru dengan menekan tombol **'Buat Laporan'**.*
>
> *Pada formulir ini, warga dapat mengunggah foto sampah baik melalui drag-and-drop file maupun langsung dari kamera. Begitu foto dipilih, sistem secara otomatis mengirimkan foto ke service AI di latar belakang.*
>
> *AI langsung menganalisis citra tersebut dan memberikan hasil klasifikasi secara instan beserta persentase keyakinan dan rekomendasi penanganan ramah lingkungan. Kategori pada dropdown otomatis terisi sesuai hasil AI.*
>
> *Selain itu, form ini dilengkapi fitur **GPS Interaktif** menggunakan Leaflet Maps. Warga cukup mengklik tombol 'Gunakan GPS Saya' atau menggeser pin marker ke lokasi tumpukan sampah yang dilaporkan.*
>
> *Formulir ini juga memiliki fitur auto-save draft ke IndexedDB browser, sehingga data tidak akan hilang jika halaman tidak sengaja ter-refresh. Kita isi judul dan deskripsi, lalu klik **'Kirim Laporan'**. Laporan berhasil terkirim dan siap ditindaklanjuti oleh petugas."*

---

### Bagian 4: Aplikasi Mobile Warga (Flutter) (03:30 – 05:00)
> **Layar:** Pindah ke tab aplikasi Mobile Flutter (`/mobile/` atau emulator). Tunjukkan tampilan login mobile, lalu dashboard mobile.

**Kata-kata yang diucapkan:**
> *"Selanjutnya, kita beralih ke komponen kedua, yaitu **Aplikasi Mobile Warga berbasis Flutter**.*
>
> *Aplikasi mobile ini didesain modern dan serasi dengan tampilan website, menggunakan palet warna yang konsisten, komponen kartu elegan, serta navigasi yang intuitif.*
>
> *Warga dapat melakukan registrasi akun baru maupun login. Mari kita masuk dengan akun warga. Di halaman utama, warga melihat panel sapaan, ringkasan statistik laporan pribadinya, serta daftar aktivitas laporan terkini dengan badge status dan kategori yang jelas.*
>
> *Sekarang kita uji fitur **Buat Laporan** di aplikasi mobile. Warga dapat memilih foto dari galeri atau mengambil foto langsung. Begitu foto dipilih, AI Image Detection langsung berjalan secara otomatis.*
>
> *Warga juga dapat mengambil titik koordinat GPS presisi perangkat hanya dengan menekan tombol 'Gunakan GPS Saya'. Kita isi judul dan keterangannya, lalu tekan **'Kirim Laporan'**. Data langsung tersinkronisasi secara real-time ke database pusat melalui REST API Laravel."*

---

### Bagian 5: Machine Learning & Deteksi Sampah Campuran (05:00 – 06:15)
> **Layar:** Buka browser tab `/ai-lab` atau terminal curl FastAPI.

**Kata-kata yang diucapkan:**
> *"Komponen ketiga adalah **Machine Learning Image Classifier** yang di-deploy sebagai REST API independen menggunakan FastAPI pada port 8001.*
>
> *Service ini memiliki endpoint health check di `/health` yang memastikan model PyTorch dimuat secara efisien saat startup.*
>
> *Model dilatih untuk mengklasifikasikan sampah ke dalam kategori utama: **Organik**, **Plastik**, dan **Kertas**. Selain itu, sistem telah kami tingkatkan dengan fitur **Spatial Multi-Region Analysis**:*
> *Ketika ada foto tumpukan sampah yang bercampur—misalnya terdapat botol plastik bersama kardus atau sisa makanan—AI secara cerdas mendeteksi multikategori tersebut dan otomatis menetapkannya ke dalam kategori **Sampah Campuran (`other`)**, lengkap dengan rekomendasi pemilahan sampah ke TPS3R.*
>
> *Jika citra tidak jelas atau meragukan, sistem menerapkan mekanisme *selective classification* dengan status `uncertain`, sehingga warga tetap dapat memilih kategori secara manual tanpa memblokir pelaporan."*

---

### Bagian 6: Dataset, Evaluasi Model & Confusion Matrix (06:15 – 07:15)
> **Layar:** Tampilkan file `machine-learning/artifacts/confusion_matrix.png` atau tabel evaluasi di halaman `/ai-lab`.

**Kata-kata yang diucapkan:**
> *"Untuk melatih model ini, kami menggunakan dataset sebanyak 150 gambar berlisensi Apache-2.0 dari Hugging Face yang terdistribusi seimbang: 50 organik, 50 kertas, dan 50 plastik. Seluruh hash SHA-256 dan atribusi data tercatat transparan di file `attributions.csv`.*
>
> *Model dibangun di atas arsitektur **MobileNetV3 Small** dengan transfer learning ImageNet, dilatih menggunakan optimasi AdamW dengan differential learning rate, Cosine Annealing scheduler, dan label smoothing.*
>
> *Berdasarkan hasil evaluasi pada data testing independen (stratified holdout test set):*
> - *Target akurasi minimal 80% berhasil terlampaui dengan akurasi pengujian sebesar **86,96%** (bahkan mencapai 100% pada evaluasi terarah).*
> - *Macro precision mencapai **90,0%** dan macro recall **87,5%**.*
> - *Confusion matrix menunjukkan model mampu membedakan karakteristik visual sampah dengan sangat baik."*

---

### Bagian 7: Dokumentasi REST API & Skema Database (07:15 – 08:00)
> **Layar:** Buka halaman `/api-docs` di browser, lalu buka file `docs/ERD.md` di VS Code.

**Kata-kata yang diucapkan:**
> *"Seluruh integrasi antar-komponen terhubung melalui REST API yang terdokumentasi lengkap dan dapat diakses langsung pada menu web `/api-docs`.*
>
> *Dokumentasi mencakup endpoint autentikasi Sanctum (Register, Login, Profil, Logout), endpoint publik klasifikasi AI (`POST /api/v1/ai/classify`), serta endpoint CRUD Laporan dan update status.*
>
> *Untuk desain database, skema relasional dirancang menggunakan SQLite untuk development dan PostgreSQL untuk production. Tabel utama terdiri dari `users` dengan pemisahan role admin dan user, tabel `reports` yang menyimpan metadata laporan, foto, koordinat GPS, serta hasil inferensi AI, dan tabel `report_status_logs` untuk audit trail riwayat perubahan status."*

---

### Bagian 8: Pengujian Otomatis & Penutup (08:00 – 08:45)
> **Layar:** Tunjukkan terminal yang menjalankan `php artisan test` dan `flutter test`.

**Kata-kata yang diucapkan:**
> *"Kualitas kode dan keandalan sistem kami validasi melalui rangkaian unit dan feature test otomatis:*
> - *Backend Laravel memiliki **49 automated tests** (249 assertions) yang mencakup autentikasi, otorisasi role, validasi input, penyimpanan draf, hingga integrasi AI.*
> - *Aplikasi Flutter dilengkapi widget dan model parser unit tests yang semuanya lulus 100%.*
> - *Begitu pula dengan FastAPI contract tests yang memvalidasi integritas endpoint prediksi.*
>
> *Seluruh source code, dataset, model, dokumentasi API, dan panduan instalasi telah kami rapikan di repository GitHub.*
>
> *Demikian demonstrasi Smart Waste Management System ini. Terima kasih kepada tim rekrutmen Bilcode Technology atas kesempatan yang diberikan. Saya sangat antusias untuk berdiskusi lebih lanjut pada tahap wawancara teknis. Terima kasih."*

---

## 4. Tips Saat Merekam
1. **Resolusi Rekaman:** Gunakan resolusi minimal 1080p (Full HD) dengan audio yang jernih (gunakan headset/mic).
2. **Tempo Bicara:** Jangan terburu-buru; naskah di atas sudah dirancang berdurasi 7,5 sampai 8,5 menit jika dibaca dengan tempo normal.
3. **Kerahasiaan:** Pastikan file `.env` yang memuat password tidak sengaja terbuka saat merekam.
4. **Setelah Selesai:** Upload video ke Google Drive / YouTube (Unlisted), pastikan link-nya dapat dibuka oleh siapa saja yang memiliki link (*Anyone with the link can view*), lalu cantumkan link tersebut di form pengumpulan.
