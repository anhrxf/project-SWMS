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
            @if(config('mail.default') === 'log')
                Akun Anda menggunakan alamat <strong>{{ auth()->user()->email }}</strong>.
                Mode email saat ini hanya mencatat pesan secara lokal.
            @else
                Tautan verifikasi dikirim ke <strong>{{ auth()->user()->email }}</strong>.
            @endif
            Buka tautan tersebut untuk memastikan Anda memiliki akses ke alamat email ini.
        </p>
    </div>

    @if($expiresAt)
        <div
            x-data="{
                expiresAt: @js($expiresAt * 1000),
                remainingSeconds: {{ max(0, $expiresAt - now()->timestamp) }},
                updateRemaining() {
                    this.remainingSeconds = Math.max(0, Math.ceil((this.expiresAt - Date.now()) / 1000));
                },
                get formattedRemaining() {
                    const minutes = Math.floor(this.remainingSeconds / 60);
                    const seconds = this.remainingSeconds % 60;
                    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
            }"
            x-init="updateRemaining(); const interval = setInterval(() => updateRemaining(), 1000); window.addEventListener('pagehide', () => clearInterval(interval), { once: true })"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs leading-5 text-emerald-900"
        >
            <p x-show="remainingSeconds > 0">
                Tautan verifikasi kedaluwarsa dalam
                <strong class="font-mono text-sm tabular-nums" role="timer" aria-label="Waktu tersisa" x-text="formattedRemaining">{{ gmdate('i:s', max(0, $expiresAt - now()->timestamp)) }}</strong>.
                Tautan berlaku selama 5 menit.
            </p>
            <p x-show="remainingSeconds <= 0" x-cloak>
                Tautan verifikasi telah kedaluwarsa. Kirim ulang untuk mendapatkan tautan baru.
            </p>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
            Waktu berlaku tautan sebelumnya tidak tersedia. Kirim ulang tautan untuk memulai hitung mundur 5 menit.
        </div>
    @endif

    @if(config('mail.default') === 'log')
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-xs leading-5 text-amber-950">
            Mailer aplikasi masih menggunakan mode log. Email <strong>tidak dikirim ke inbox</strong>;
            untuk pengembangan lokal, isi tautannya tercatat di <code>storage/logs/laravel.log</code>.
            Atur SMTP Gmail di file <code>.env</code> untuk pengiriman nyata.
        </div>
    @endif

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
