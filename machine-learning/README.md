# Klasifikasi Sampah SWMS

Layanan ini melatih model PyTorch MobileNetV3 Small untuk mengklasifikasikan foto
ke tiga kelas: `organic`, `plastic`, dan `paper`. FastAPI menyediakan prediksi
untuk website Laravel dan aplikasi Flutter.

## Dataset dan lisensi

`training_photos/` berisi 150 foto (50 per kelas) dari dataset
[`NeoAivara/Waste_Classification_data`](https://huggingface.co/datasets/NeoAivara/Waste_Classification_data/tree/d58fa9b9187dfd63b726f6f955e2414c0c7fda4d),
revision `d58fa9b9187dfd63b726f6f955e2414c0c7fda4d`. Dataset sumber menyatakan
lisensi Apache-2.0. Label `biological` dipetakan menjadi `organic`; label
`paper` dan `plastic` dipertahankan. Manifest `training_photos/attributions.csv`
mencatat revision, row sumber, lisensi, URL, dan SHA-256 setiap file.

Dataset kecil ini hanya sampel untuk technical test dan tidak mewakili semua
kondisi sampah di dunia nyata. Gambar bertema bahan/produk tidak selalu
menunjukkan sampah yang sudah dibuang. Interpretasikan hasil evaluasi dan
prediksi dengan batasan tersebut; model bukan pengganti pemeriksaan petugas.

Jika dataset tidak tersedia, ambil ulang subset yang sama:

```powershell
python -m pip install -r requirements.txt
python download_training_photos.py
```

Model hanya mengenali tiga kelas tersebut. Saat prediksi, probabilitas dari foto
asli dan foto yang dibalik horizontal dirata-ratakan. Kategori hanya ditetapkan
jika kedua tampilan sepakat dan skor tertinggi minimal 70%; jika tidak, hasilnya
`uncertain` tanpa kategori atau rekomendasi otomatis. Skor softmax bukan
probabilitas terkalibrasi bahwa prediksi pasti benar. Aturan ini mengurangi
prediksi meragukan, tetapi tidak menjamin pengenalan objek di luar tiga kelas.

## Training dan evaluasi

Jalankan dari folder `machine-learning`:

```powershell
python -m pip install -r requirements.txt
python train.py
```

Pipeline memeriksa ketiga kelas dan minimal 150 gambar unik, membagi data secara
stratified menjadi train/validation/test, memilih checkpoint berdasarkan
validation loss, lalu mengevaluasi sekali pada test split. Random seed adalah
42. Transfer learning memakai bobot ImageNet MobileNetV3; saat pertama kali
training, bobot tersebut perlu diunduh. `--epochs`, `--batch-size`, dan
`--patience` dapat digunakan untuk menyesuaikan training.

Hasil ditulis ke `artifacts/`:

- `model.pt`: checkpoint PyTorch dan metadata kelas.
- `evaluation.json`: akurasi test, classification report, confusion matrix,
  ukuran split, lisensi dataset, status target 80%, dan coverage/akurasi selective
  classification.
- `confusion_matrix.png`: visualisasi confusion matrix test.
- `training_history.json`: loss dan akurasi train/validation tiap epoch.
- `test_predictions.csv`: label aktual dan prediksi per foto pada test split.

### Hasil evaluasi saat ini

Training terakhir (seed 42, checkpoint terbaik epoch 1) menghasilkan akurasi
test **91,30%** pada 23 foto: tujuh organic, delapan paper, dan delapan plastic.
Selective classification menerapkan ambang 70% dan kesepakatan dua tampilan.
Evaluasi awal pada checkpoint ini menerima 16 dari 23 foto uji; 16 foto yang
diterima benar. Macro precision, recall, dan F1-score keseluruhan masing-masing
**91,67%**. Matriksnya
(urutan baris/kolom: organic, paper, plastic):

```text
             organic  paper  plastic
organic           7      0        0
paper             0      7        1
plastic           0      1        7
```

Dengan demikian, target test 80% tercapai pada split ini (`target_accuracy_met`
di `evaluation.json` bernilai `true`). Test hanya berisi 23 foto dari subset
150-foto yang kecil; hasil ini tidak membuktikan generalisasi pada semua jenis
sampah, kamera, pencahayaan, atau latar. Jangan menganggapnya sebagai jaminan
akurasi produksi.

## Menjalankan FastAPI

FastAPI memerlukan `artifacts/model.pt`; tanpa checkpoint, startup gagal secara
eksplisit dan tidak ada prediksi fallback.

```powershell
python app.py
```

Service berjalan pada `http://127.0.0.1:8001`. Dokumentasi interaktif ada di
`http://127.0.0.1:8001/docs`. Health check:

```powershell
Invoke-RestMethod http://127.0.0.1:8001/health
```

Contoh prediksi memakai PowerShell:

```powershell
curl.exe -X POST "http://127.0.0.1:8001/predict" -F "file=@training_photos/plastic/plastic_001.jpg"
```

Endpoint menerima satu gambar maksimum 10 MB dan 25 megapiksel. File kosong,
gambar tidak valid, serta file/dimensi terlalu besar ditolak dengan status
HTTP yang sesuai.

## Integrasi Laravel

Laravel memanggil `POST /predict` dengan multipart field `file`. Atur endpoint
melalui `ML_API_URL` di root `.env` (default
`http://127.0.0.1:8001/predict`). Kategori dan confidence selalu dihitung ulang
di server dari gambar; nilai yang disertakan klien tidak dipercaya. Bila
FastAPI tidak tersedia atau model gagal, Laravel mengembalikan HTTP 503 dan
tidak menyimpan laporan ataupun foto sementara.
