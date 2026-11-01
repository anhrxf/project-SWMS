@extends('layouts.guest')

@section('title', 'Daftar Akun Baru')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Daftar Akun Warga</h2>
        <p class="text-xs text-slate-500 mt-1">Buat akun untuk melaporkan timbunan sampah di lingkungan Anda.</p>
    </div>

    <form method="POST" action="{{ route('register.post') }}" class="space-y-3.5">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-user text-xs"></i>
                </span>
                <input id="name" 
                       name="name" 
                       type="text" 
                       required 
                       value="{{ old('name') }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                       placeholder="Contoh: Budi Santoso">
            </div>
            @error('name')
                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                Alamat Email <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-envelope text-xs"></i>
                </span>
                <input id="email" 
                       name="email" 
                       type="email" 
                       required 
                       value="{{ old('email') }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                       placeholder="nama@email.com">
            </div>
            @error('email')
                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nomor HP / WhatsApp <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-phone text-xs"></i>
                    </span>
                    <input id="phone" 
                           name="phone" 
                           type="tel" 
                           required
                           value="{{ old('phone') }}"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                           placeholder="08123456789">
                </div>
                @error('phone')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Alamat / Wilayah</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-location-dot text-xs"></i>
                    </span>
                    <input id="address" 
                           name="address" 
                           type="text" 
                           value="{{ old('address') }}"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:outline-none focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 transition" 
                           placeholder="Kelurahan / Kota">
                </div>
            </div>
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi</label>
            <div class="relative" x-data="{ show: false }">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-lock text-xs"></i>
                </span>
                <input id="password" 
                       name="password" 
                       :type="show ? 'text' : 'password'" 
                       required 
                       class="w-full pl-10 pr-10 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                       placeholder="Minimal 8 karakter">
                <button type="button" 
                        @click="show = !show" 
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                    <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-lock-open text-xs"></i>
                </span>
                <input id="password_confirmation" 
                       name="password_confirmation" 
                       type="password" 
                       required 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:outline-none focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 transition" 
                       placeholder="Ulangi kata sandi">
            </div>
        </div>

        <button type="submit" 
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-semibold text-xs bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 shadow-lg shadow-emerald-600/30 transition mt-2">
            <i class="fa-solid fa-user-plus"></i>
            <span>Daftar Akun Sekarang</span>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Sudah memiliki akun? 
            <a href="{{ route('login') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">
                Masuk di sini
            </a>
        </p>
    </div>
</div>
@endsection
