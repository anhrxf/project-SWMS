@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')
<div class="space-y-5">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
            <i class="fa-solid fa-envelope-circle-check text-xl"></i>
        </div>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Verifikasi email Anda</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Kami mengirim tautan verifikasi ke <strong>{{ auth()->user()->email }}</strong>.
            Buka tautan tersebut untuk memastikan Anda memiliki akses ke alamat email ini.
        </p>
    </div>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs leading-5 text-emerald-900">
        Tautan berlaku selama 60 menit. Periksa folder spam atau kirim ulang tautan jika belum menerima email.
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
                class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-xs font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
            Kirim ulang tautan verifikasi
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
            Keluar dari akun
        </button>
    </form>
</div>
@endsection
