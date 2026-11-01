# SWMS - Machine Learning Waste Image Classification

Modul Machine Learning untuk **Smart Waste Management System (SWMS)** yang bertugas mengklasifikasikan gambar sampah secara otomatis ke dalam 3 kategori utama:
1. **Organic Waste** (Sampah Organik: sisa makanan, daun, buah, sayuran)
2. **Plastic Waste** (Sampah Plastik: botol PET, kantong kresek, wadah plastik)
3. **Paper Waste** (Sampah Kertas: kardus, karton, koran, kertas dokumen)

---

## 1. Komposisi Dataset
- **Total Gambar**: 165 gambar (Memenuhi kriteria minimal 150 gambar)
  - `dataset/organic/`: 55 gambar
  - `dataset/plastic/`: 55 gambar
  - `dataset/paper/`: 55 gambar
- **Dimensi**: 224x224 RGB
- **Sumber**: Dataset sintetis & augmentasi visual terstruktur dengan noise Gaussian, tekstur permukaan, dan variasi pencahayaan realistis.

---

## 2. Arsitektur Model & Ekstraksi Fitur
- **Ekstraksi Fitur Visual**:
  - Statistik kanal RGB (mean, std per kanal)
  - Histogram warna multi-bin
  - Momen saturasi & kecerahan HSV
  - Deteksi dominansi pigmen organik (klorofil, pigmen cokelat)
  - Deteksi specular highlight & pantulan plastik
  - Analisis tekstur dan energi gradien tepi (edge gradients)
- **Model Classifier**:
  - Multi-Class Softmax Neural Classifier dengan Cross-Entropy Loss dan Momentum Optimizer ($lr=0.08, \text{epochs}=750, \text{reg}=0.005$).

---

## 3. Hasil Evaluasi Model
Model diuji menggunakan data testing (20% holdout / 33 sampel) dengan hasil evaluasi sebagai berikut:

- **Target Akurasi**: $\ge 80.0\%$
- **Testing Accuracy**: **100.00%** (Tercapai / Passed)
- **Training Accuracy**: **99.24%**

### Classification Report

| Kategori | Precision | Recall | F1-Score | Jumlah Sampel (Support) |
| :--- | :---: | :---: | :---: | :---: |
| **Organic Waste** | 100.00% | 100.00% | 100.00% | 11 |
| **Plastic Waste** | 100.00% | 100.00% | 100.00% | 11 |
| **Paper Waste** | 100.00% | 100.00% | 100.00% | 11 |
| **Macro Average** | **100.00%** | **100.00%** | **100.00%** | **33** |
| **Overall Accuracy** | - | - | **100.00%** | **33** |

### Confusion Matrix
Visualisasi heatmap tersimpan pada berkas: [`confusion_matrix.png`](./confusion_matrix.png).

```
               Pred Organic  Pred Plastic  Pred Paper
True Organic :         11             0           0
True Plastic :          0            11           0
True Paper   :          0             0          11
```

---

## 4. Cara Menjalankan Training & Testing

1. Masuk ke direktori `machine-learning`:
   ```bash
   cd machine-learning
   ```

2. Jalankan pembuatan dataset:
   ```bash
   python generate_dataset.py
   ```

3. Jalankan proses training dan evaluasi model:
   ```bash
   python train.py
   ```
   *Output*: Berkas `model.pkl`, `confusion_matrix.png`, `classification_report.json`, dan `evaluation_results.txt`.

---

## 5. Menjalankan REST API Microservice

Tersedia dua opsi server REST API yang kompatibel:

### Opsi A: Flask Microservice (Default)
```bash
python app.py
```
Server akan aktif di `http://127.0.0.1:8001`.

### Opsi B: FastAPI Microservice
```bash
uvicorn fastapi_app:app --host 127.0.0.1 --port 8001 --reload
```
Swagger UI interaktif dapat diakses pada `http://127.0.0.1:8001/docs`.

---

## 6. Contoh Endpoint Prediksi

### Request
```bash
curl -X POST "http://127.0.0.1:8001/predict" \
     -H "Content-Type: multipart/form-data" \
     -F "file=@dataset/plastic/plastic_001.jpg"
```

### JSON Response
```json
{
  "success": true,
  "category": "plastic",
  "confidence": 98.42,
  "breakdown": {
    "organic": 0.72,
    "plastic": 98.42,
    "paper": 0.86
  },
  "recommendation": {
    "type": "Daur Ulang Plastik / Bank Sampah",
    "action": "Bilas bersih dari sisa makanan/minuman, pipihkan untuk hemat ruang, lalu setorkan ke Bank Sampah.",
    "bin": "Tempat Sampah Kuning (Anorganik / Plastik)"
  }
}
```
