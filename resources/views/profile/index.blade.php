@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('page_title', 'Pengaturan Profil')
@section('page_subtitle', 'Kelola informasi pribadi dan keamanan akun Anda')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl uppercase shadow-md">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                <div class="mt-1">
                    @if($user->isAdmin())
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                            <i class="fa-solid fa-shield-halved text-[9px]"></i> Administrator
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="fa-solid fa-user text-[9px]"></i> Warga / User
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-bold text-slate-800 mb-1">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       required 
                       value="{{ old('name', $user->name) }}" 
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
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
                       value="{{ old('email', $user->email) }}" 
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-800 mb-1">
                        Nomor HP <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           required
                           value="{{ old('phone', $user->phone) }}" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-rose-400' : 'border-slate-200' }} text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold text-slate-800 mb-1">Alamat</label>
                    <input type="text" 
                           id="address" 
                           name="address" 
                           value="{{ old('address', $user->address) }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <span class="block text-xs font-bold text-slate-700 mb-2">Ubah Kata Sandi (Kosongkan bila tidak ingin diganti)</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <input type="password" 
                               name="password" 
                               placeholder="Kata sandi baru" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                    </div>
                    <div>
                        <input type="password" 
                               name="password_confirmation" 
                               placeholder="Ulangi kata sandi baru" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
