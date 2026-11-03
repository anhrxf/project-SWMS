@extends('layouts.guest')

@section('title', 'Masuk ke Sistem')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Masuk ke Akun Anda</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan alamat email dan kata sandi terdaftar untuk melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4" autocomplete="off">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-envelope text-xs"></i>
                </span>
                <input id="email" 
                       name="login_email"
                       type="text"
                       inputmode="email"
                       autocomplete="new-password"
                       autocapitalize="none"
                       autocorrect="off"
                       spellcheck="false"
                       required 
                       value="{{ request()->cookie('last_login_email', '') }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                       placeholder="nama@email.com">
            </div>
            @error('email')
                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-slate-700">Kata Sandi</label>
            </div>
            <div class="relative" x-data="{ show: false }">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-lock text-xs"></i>
                </span>
                <input id="password" 
                       name="password" 
                       :type="show ? 'text' : 'password'" 
                       autocomplete="new-password"
                       required 
                       class="w-full pl-10 pr-10 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:outline-none focus:ring-4 focus:border-emerald-500 transition" 
                       placeholder="••••••••">
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

        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                <span class="text-xs text-slate-600">Ingat saya</span>
            </label>
        </div>

        <button type="submit" 
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-semibold text-xs bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 shadow-md shadow-emerald-600/20 transition">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            <span>Masuk ke Sistem</span>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Belum memiliki akun warga? 
            <a href="{{ route('register') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">
                Daftar sekarang
            </a>
        </p>
    </div>
</div>
@endsection
