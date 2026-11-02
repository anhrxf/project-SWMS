<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('info', 'Verifikasi email Anda sebelum menggunakan aplikasi.');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Alamat email salah.',
        ])->onlyInput('email');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $emailRule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', $emailRule, 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'unique:users,phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Email tidak valid.',
            'email.unique' => 'Alamat email ini sudah pernah terdaftar sebelumnya. Silakan gunakan email yang berbeda.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.unique' => 'Nomor telah terdaftar.',
            'phone.min' => 'Nomor HP minimal terdiri dari 9 digit angka.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'role' => 'user', // Default registered users are regular users
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $expiresAt = now()->addMinutes(config('auth.verification.expire', 5));
        $user->sendVerificationNotificationUntil($expiresAt);
        $request->session()->put('email_verification_expires_at', $expiresAt->timestamp);

        return redirect()->route('verification.notice')
            ->with(
                'info',
                config('mail.default') === 'log'
                    ? 'Akun berhasil dibuat, tetapi mailer log tidak mengirim email ke inbox. Atur SMTP Gmail di .env.'
                    : 'Akun berhasil dibuat. Periksa email Anda untuk memverifikasi akun sebelum masuk.'
            );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    public function profile(): View
    {
        return view('profile.index', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $emailRule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', $emailRule, 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'unique:users,phone,' . $user->id],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain. Gunakan email yang berbeda.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.unique' => 'Nomor telah terdaftar.',
            'phone.min' => 'Nomor HP minimal terdiri dari 9 digit angka.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
        ];

        $emailChanged = strcasecmp($user->email, $validated['email']) !== 0;

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if ($emailChanged) {
            $user->markEmailAsUnverified();
            $expiresAt = now()->addMinutes(config('auth.verification.expire', 5));
            $user->sendVerificationNotificationUntil($expiresAt);
            $request->session()->put('email_verification_expires_at', $expiresAt->timestamp);

            return redirect()->route('verification.notice')
                ->with(
                    'info',
                    config('mail.default') === 'log'
                        ? 'Email diperbarui, tetapi mailer log tidak mengirim tautan ke inbox. Atur SMTP Gmail di .env.'
                        : 'Email diperbarui. Verifikasi alamat email baru sebelum menggunakan aplikasi.'
                );
        }

        return back()->with('success', 'Profil Anda berhasil diperbarui!');
    }
}
