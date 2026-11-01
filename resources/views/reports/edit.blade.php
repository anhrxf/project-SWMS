@extends('layouts.app')

@section('title', 'Edit Laporan #' . $report->id)
@section('page_title', 'Perbarui Data Laporan')
@section('page_subtitle', 'Edit rincian, foto, atau lokasi untuk laporan #' . $report->id)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('reports.show', $report) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Detail Laporan
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-xs font-bold text-slate-800 mb-1">
                    Judul Laporan <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       required 
                       value="{{ old('title', $report->title) }}" 
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('title') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                @error('title')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="waste_category" class="block text-xs font-bold text-slate-800 mb-1">
                    Kategori Sampah <span class="text-rose-500">*</span>
                </label>
                <select id="waste_category" 
                        name="waste_category" 
                        required 
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition bg-white">
                    <option value="organic" {{ old('waste_category', $report->waste_category) === 'organic' ? 'selected' : '' }}>Sampah Organik</option>
                    <option value="plastic" {{ old('waste_category', $report->waste_category) === 'plastic' ? 'selected' : '' }}>Sampah Plastik</option>
                    <option value="paper" {{ old('waste_category', $report->waste_category) === 'paper' ? 'selected' : '' }}>Sampah Kertas</option>
                    <option value="other" {{ old('waste_category', $report->waste_category) === 'other' ? 'selected' : '' }}>Lainnya / Campuran</option>
                </select>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-800 mb-1">
                    Deskripsi Kondisi Sampah <span class="text-rose-500">*</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="4" 
                          required 
                          class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('description') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">{{ old('description', $report->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Ganti Foto Sampah (Opsional)</label>
                <div class="flex items-center gap-4">
                    <img src="{{ $report->image_url }}" alt="Current Photo" class="w-16 h-16 rounded-xl object-cover border border-slate-200">
                    <input type="file" 
                           name="image" 
                           accept="image/jpeg,image/png,image/jpg,image/webp" 
                           class="text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
            </div>

            <div>
                <label for="location_address" class="block text-xs font-bold text-slate-800 mb-1">
                    Alamat / Patokan Lokasi
                </label>
                <input type="text" 
                       id="location_address" 
                       name="location_address" 
                       value="{{ old('location_address', $report->location_address) }}" 
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('reports.show', $report) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
