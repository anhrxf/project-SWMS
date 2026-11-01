@extends('layouts.app')

@section('title', 'Dokumentasi REST API')
@section('page_title', 'Dokumentasi REST API SWMS')
@section('page_subtitle', 'Spesifikasi endpoint REST API untuk integrasi Mobile App dan Third-Party Service')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Card -->
    <div class="p-6 rounded-3xl bg-slate-900 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold border border-emerald-500/30 mb-2">
                <i class="fa-solid fa-code"></i> RESTful API v1
            </div>
            <h2 class="text-xl font-extrabold tracking-tight">Smart Waste Management System API</h2>
            <p class="text-xs text-slate-300 mt-1">Base URL: <code class="px-2 py-0.5 rounded bg-white/10 font-mono text-emerald-400">{{ url('/api/v1') }}</code></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/10 border border-white/20">
                Bearer Token (Laravel Sanctum)
            </span>
        </div>
    </div>

    <!-- Endpoints List -->
    <div class="space-y-4">

        <!-- 1. POST /auth/register -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/auth/register</code>
                <span class="ml-auto text-[11px] text-slate-400 font-medium">Publik</span>
            </div>
            <p class="text-xs text-slate-600">Registrasi akun baru untuk pengguna aplikasi mobile atau web.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "name": "Budi Santoso",
  "email": "budi@email.com",
  "phone": "08123456789",
  "password": "password123",
  "password_confirmation": "password123"
}</pre>
            </div>
        </div>

        <!-- 2. POST /auth/login -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/auth/login</code>
                <span class="ml-auto text-[11px] text-slate-400 font-medium">Publik</span>
            </div>
            <p class="text-xs text-slate-600">Autentikasi kredensial dan mendapatkan token Sanctum (Bearer Token).</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "email": "user@swms.com",
  "password": "password",
  "device_name": "Flutter_Android"
}</pre>
            </div>
        </div>

        <!-- 3. POST /ai/classify -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-amber-100 text-amber-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/ai/classify</code>
                <span class="ml-auto text-[11px] text-amber-600 font-bold"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Engine</span>
            </div>
            <p class="text-xs text-slate-600">Prediksi klasifikasi gambar sampah (multipart/form-data: <code class="bg-slate-100 px-1 rounded">image</code>) menghasilkan kategori, confidence score, dan panduan daur ulang.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "success": true,
  "data": {
    "category": "plastic",
    "confidence": 93.8,
    "label": "Sampah Plastik (Plastic Waste)",
    "breakdown": {
      "organic": 2.1,
      "plastic": 93.8,
      "paper": 4.1
    },
    "recommendation": {
      "type": "Daur Ulang Plastik / Bank Sampah",
      "action": "Bilas bersih dan kemas rapi.",
      "bin": "Tempat Sampah Kuning (Plastik)"
    }
  }
}</pre>
            </div>
        </div>

        <!-- 4. GET /reports -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800">GET</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/reports</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Mendapatkan daftar laporan sampah (dengan query params: <code class="bg-slate-100 px-1 rounded">status</code>, <code class="bg-slate-100 px-1 rounded">category</code>, <code class="bg-slate-100 px-1 rounded">search</code>, <code class="bg-slate-100 px-1 rounded">page</code>).</p>
        </div>

        <!-- 5. POST /reports -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/reports</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Mengirim laporan sampah baru (multipart/form-data: <code class="bg-slate-100 px-1 rounded">title</code>, <code class="bg-slate-100 px-1 rounded">description</code>, <code class="bg-slate-100 px-1 rounded">waste_category</code>, <code class="bg-slate-100 px-1 rounded">image</code>, <code class="bg-slate-100 px-1 rounded">latitude</code>, <code class="bg-slate-100 px-1 rounded">longitude</code>, <code class="bg-slate-100 px-1 rounded">location_address</code>).</p>
        </div>

        <!-- 6. GET /reports/{id} -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800">GET</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/reports/{id}</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-lock"></i> Auth Sanctum</span>
            </div>
            <p class="text-xs text-slate-600">Detail laporan lengkap beserta foto URL, histori status logs, koordinat GPS, dan rekomendasi daur ulang.</p>
        </div>

        <!-- 7. POST /reports/{id}/status (Admin) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-purple-100 text-purple-800">POST</span>
                <code class="text-xs font-bold text-slate-800 font-mono">/api/v1/reports/{id}/status</code>
                <span class="ml-auto text-[11px] text-purple-600 font-bold"><i class="fa-solid fa-shield-halved"></i> Admin Only</span>
            </div>
            <p class="text-xs text-slate-600">Memperbarui status laporan (Pending &rarr; In Progress &rarr; Completed &rarr; Rejected) dan menyematkan catatan tindakan audit.</p>
            <div class="bg-slate-900 text-slate-200 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">
<pre>{
  "status": "completed",
  "admin_notes": "Timbunan sampah telah diangkut oleh truk pengangkut DLH."
}</pre>
            </div>
        </div>

    </div>

</div>
@endsection
