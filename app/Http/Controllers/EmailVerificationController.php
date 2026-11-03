<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if (!config('auth.email_verification_enabled')) {
            return redirect()->route('dashboard')
                ->with('info', 'Verifikasi email sementara dinonaktifkan.');
        }

        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $expiresAt = $request->session()->get('email_verification_expires_at');

        return view('auth.verify-email', [
            'expiresAt' => is_numeric($expiresAt) ? (int) $expiresAt : null,
        ]);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Tautan verifikasi email tidak valid.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $request->session()->forget('email_verification_expires_at');

        if (Auth::check() && Auth::id() === $user->id) {
            return redirect()->route('dashboard')
                ->with('success', 'Email berhasil diverifikasi. Akun Anda sudah aktif.');
        }

        return redirect()->route('login')
            ->with('success', 'Email berhasil diverifikasi. Silakan masuk ke akun Anda.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!config('auth.email_verification_enabled')) {
            return redirect()->route('dashboard')
                ->with('info', 'Verifikasi email sementara dinonaktifkan.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $expiresAt = now()->addMinutes(config('auth.verification.expire', 5));
        $user->sendVerificationNotificationUntil($expiresAt);
        $request->session()->put('email_verification_expires_at', $expiresAt->timestamp);

        return back()->with(
            'info',
            config('mail.default') === 'log'
                ? 'Mailer log tidak mengirim email ke inbox. Atur SMTP Gmail di .env untuk pengiriman nyata.'
                : 'Tautan verifikasi baru telah dikirim ke alamat email Anda.'
        );
    }

}
