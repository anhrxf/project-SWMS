@extends('layouts.app')

@section('title', 'Dokumentasi REST API')
@section('page_title', 'Dokumentasi REST API SWMS')
@section('page_subtitle', 'Spesifikasi endpoint REST API untuk integrasi aplikasi dan layanan lain')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="p-6 rounded-3xl bg-slate-900 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold border border-emerald-500/30 mb-2">
                <i class="fa-solid fa-code"></i> REST API v1
            </div>
            <h2 class="text-xl font-extrabold tracking-tight">Smart Waste Management System API</h2>
            <p class="text-xs text-slate-300 mt-1">Base URL: <code class="px-2 py-0.5 rounded bg-white/10 font-mono text-emerald-400">{{ url('/api/v1') }}</code></p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/10 border border-white/20">Bearer token · Laravel Sanctum</span>
    </div>

    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 space-y-2">
        <h3 class="text-sm font-bold text-emerald-950">Autentikasi dan format request</h3>
        <p class="text-xs text-emerald-900">Endpoint terautentikasi memerlukan header <code class="rounded bg-white px-1.5 py-0.5 font-mono">Authorization: Bearer &lt;token&gt;</code> dan <code class="rounded bg-white px-1.5 py-0.5 font-mono">Accept: application/json</code>. Token diperoleh dari register atau login.</p>
        <p class="text-xs text-emerald-900">Gunakan JSON untuk request biasa. Untuk unggah gambar, gunakan <code class="rounded bg-white px-1.5 py-0.5 font-mono">multipart/form-data</code>; biarkan aplikasi klien mengatur boundary Content-Type.</p>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/auth/register</code>
                <span class="ml-auto text-[11px] text-slate-400 font-medium">Publik</span>
            </div>
            <p class="text-xs text-slate-600">Membuat akun warga. Email harus valid dan unik; nomor HP wajib, unik, dan 9–20 karakter; alamat opsional. Mengirim tautan verifikasi ke email. Respons HTTP 201 menyertakan token, <code>email_verified: false</code>, dan <code>verification_url</code>. Buka tautan verifikasi sebelum menggunakan endpoint laporan.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "name": "Budi Santoso",
  "email": "budi@email.com",
  "phone": "08123456789",
  "address": "Jl. Merdeka No. 45",
  "password": "password123",
  "password_confirmation": "password123"
}</pre>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/auth/login</code>
                <span class="ml-auto text-[11px] text-slate-400 font-medium">Publik</span>
            </div>
            <p class="text-xs text-slate-600">Login mengembalikan HTTP 200 dan token Sanctum. Nama perangkat opsional. Jika email belum diverifikasi, respons tetap memberikan token untuk pemeriksaan profil dan kirim ulang verifikasi, tetapi endpoint laporan menolak akses.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "email": "warga@email.com",
  "password": "password123",
  "device_name": "Flutter_Android"
}</pre>
            </div>
            <p class="text-xs font-semibold text-slate-700">Respons sukses memuat profil dan token:</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {"id": 2, "name": "Budi Santoso", "email": "warga@email.com", "role": "user"},
    "token": "&lt;token&gt;",
    "token_type": "Bearer",
    "email_verified": true
  }
}</pre>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800">GET</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/auth/profile</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Mengambil id, nama, email, role, nomor HP, alamat, dan waktu pembuatan akun pengguna terautentikasi. Respons berada di dalam <code class="bg-slate-100 px-1 rounded">data</code>.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-amber-100 text-amber-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/auth/logout</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Mencabut token yang sedang digunakan. Login kembali untuk memperoleh token baru.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-amber-100 text-amber-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/auth/email/verification-notification</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Kirim ulang tautan untuk akun belum terverifikasi. Respons HTTP 202. Dibatasi hingga enam permintaan per menit.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-amber-100 text-amber-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/ai/classify</code>
                <span class="ml-auto text-[11px] text-amber-600 font-bold"><i class="fa-solid fa-wand-magic-sparkles"></i> Publik</span>
            </div>
            <p class="text-xs text-slate-600">Kirim field <code class="bg-slate-100 px-1 rounded">image</code> wajib melalui multipart/form-data (JPEG, PNG, atau WEBP; maks. 10 MB). Respons berisi kategori, confidence, label, breakdown, dan rekomendasi; tidak memerlukan token.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "success": true,
  "message": "Analisis gambar berhasil dilakukan.",
  "data": {
    "source": "builtin_smart_classifier",
    "category": "plastic",
    "confidence": 93.8,
    "label": "Sampah Plastik (Plastic Waste)",
    "breakdown": {"organic": 2.1, "plastic": 93.8, "paper": 4.1},
    "recommendation": {
      "type": "Daur Ulang Plastik / Bank Sampah",
      "action": "Bilas dan kumpulkan ke Bank Sampah terdekat.",
      "color_code": "#3b82f6",
      "bin": "Tempat Sampah Kuning (Anorganik / Plastik)"
    }
  }
}</pre>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800">GET</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/reports</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Memerlukan token dan email terverifikasi. Admin melihat seluruh laporan; warga hanya melihat laporan sendiri. Query opsional: <code>status</code> (pending, in_progress, completed, rejected), <code>category</code> (organic, plastic, paper, other), <code>search</code> (judul, deskripsi, alamat), <code>page</code>, dan <code>per_page</code> (default 15).</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/reports</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Warga · Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Buat laporan dengan multipart/form-data. Wajib: <code>title</code> (string, maks. 255), <code>description</code>, <code>waste_category</code> (organic, plastic, paper, other), dan <code>image</code> (JPEG, PNG, WEBP; maks. 10 MB). Opsional: <code>latitude</code> (-90 hingga 90), <code>longitude</code> (-180 hingga 180), <code>location_address</code> (maks. 500), <code>ai_detected_category</code>, <code>ai_confidence</code>. Admin menerima HTTP 403. Sukses mengembalikan HTTP 201.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800">GET</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/reports/{report}</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Detail laporan, termasuk <code>data.report</code>, <code>data.image_url</code>, dan <code>data.recommendation</code>. Admin dapat melihat semua laporan; warga hanya laporan miliknya. Laporan tidak ditemukan: HTTP 404; akses warga ke laporan orang lain: HTTP 403.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-purple-100 text-purple-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/reports/{report}/status</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-shield-halved"></i> Admin Only</span>
            </div>
            <p class="text-xs text-slate-600">Request JSON dengan <code>status</code> wajib (pending, in_progress, completed, rejected) dan <code>admin_notes</code> opsional (maks. 1.000 karakter). Tidak ada urutan transisi yang diwajibkan; setiap perubahan dicatat dalam riwayat status.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "status": "completed",
  "admin_notes": "Sampah telah diangkut."
}</pre>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-2">
        <h3 class="text-sm font-bold text-slate-900">Respons error umum</h3>
        <ul class="list-disc pl-5 text-xs text-slate-600 space-y-1">
            <li><code>401</code>: token tidak ada atau tidak valid.</li>
            <li><code>403</code>: role tidak diizinkan atau warga mengakses laporan orang lain.</li>
            <li><code>404</code>: laporan tidak ditemukan.</li>
            <li><code>422</code>: validasi gagal; respons berisi <code>message</code> dan detail per-field di <code>errors</code>.</li>
        </ul>
        <p class="text-xs text-slate-600">Sertakan <code>Accept: application/json</code> agar Laravel mengirim error validasi dalam format JSON.</p>
    </div>
</div>
@endsection
