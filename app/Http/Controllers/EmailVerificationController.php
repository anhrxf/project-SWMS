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
    public function notice(): View|RedirectResponse
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
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

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('info', 'Tautan verifikasi baru telah dikirim ke alamat email Anda.');
    }

}
