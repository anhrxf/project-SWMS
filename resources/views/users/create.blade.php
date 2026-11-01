@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru')
@section('page_title', 'Tambah Pengguna Baru')
@section('page_subtitle', 'Tambahkan akun administrator atau warga baru secara manual')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-purple-700 transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Pengguna
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold text-slate-800 mb-1">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       required 
                       value="{{ old('name') }}" 
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition"
                       placeholder="Contoh: Ahmad Fauzi">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-slate-800 mb-1">
                    Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       required 
                       value="{{ old('email') }}" 
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition"
                       placeholder="nama@email.com">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-xs font-bold text-slate-800 mb-1">
                        Hak Akses (Role) <span class="text-rose-500">*</span>
                    </label>
                    <select id="role" 
                            name="role" 
                            required 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition bg-white">
                        <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Warga / User Biasa</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    </select>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-800 mb-1">Nomor Telepon</label>
                    <input type="text" 
                           id="phone" 
                           name="phone" 
                           value="{{ old('phone') }}" 
                           placeholder="08123456789"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">
                </div>
            </div>

            <div>
                <label for="address" class="block text-xs font-bold text-slate-800 mb-1">Alamat</label>
                <input type="text" 
                       id="address" 
                       name="address" 
                       value="{{ old('address') }}" 
                       placeholder="Kelurahan / Kota"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-800 mb-1">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition"
                           placeholder="Minimal 8 karakter">
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-800 mb-1">
                        Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition"
                           placeholder="Ulangi kata sandi">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/30 transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
