<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ApiAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $emailRule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', $emailRule, 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'unique:users,phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Alamat email ini sudah terdaftar sebelumnya.',
            'email.email' => 'Email tidak valid.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.unique' => 'Nomor telah terdaftar.',
            'phone.min' => 'Nomor HP minimal terdiri dari 9 digit angka.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'role' => 'user',
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('swms_mobile_token')->plainTextToken;
        $verificationRequired = (bool) config('auth.email_verification_enabled');
        $expiresAt = $verificationRequired
            ? now()->addMinutes(config('auth.verification.expire', 5))
            : null;

        if ($expiresAt) {
            $user->sendVerificationNotificationUntil($expiresAt);
        }

        return response()->json([
            'success' => true,
            'message' => !$verificationRequired
                ? 'Registrasi berhasil. Verifikasi email sementara dinonaktifkan.'
                : (config('mail.default') === 'log'
                    ? 'Registrasi berhasil, tetapi mailer log tidak mengirim email ke inbox. Atur SMTP untuk pengiriman nyata.'
                    : 'Registrasi berhasil. Periksa inbox untuk tautan verifikasi.'),
            'email_sent' => $verificationRequired && config('mail.default') !== 'log',
            'email_verification_required' => $verificationRequired,
            'verification_expires_at' => $expiresAt?->toIso8601String(),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'address' => $user->address,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'email_verified' => false,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email salah.',
            ], 401);
        }

        $deviceName = $validated['device_name'] ?? 'swms_mobile_app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => !config('auth.email_verification_enabled')
                ? 'Login berhasil. Verifikasi email sementara dinonaktifkan.'
                : ($user->hasVerifiedEmail()
                    ? 'Login berhasil.'
                    : 'Login berhasil, tetapi verifikasi email diperlukan sebelum menggunakan laporan.'),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'address' => $user->address,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'email_verified' => $user->hasVerifiedEmail(),
                'email_verification_required' => (bool) config('auth.email_verification_enabled'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout dan token dicabut.',
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!config('auth.email_verification_enabled')) {
            return response()->json([
                'success' => true,
                'message' => 'Verifikasi email sementara dinonaktifkan.',
                'email_sent' => false,
                'email_verification_required' => false,
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat email sudah diverifikasi.',
            ]);
        }

        $expiresAt = now()->addMinutes(config('auth.verification.expire', 5));
        $user->sendVerificationNotificationUntil($expiresAt);

        return response()->json([
            'success' => config('mail.default') !== 'log',
            'message' => config('mail.default') === 'log'
                ? 'Mailer log tidak mengirim email ke inbox. Atur SMTP untuk pengiriman nyata.'
                : 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
            'email_sent' => config('mail.default') !== 'log',
            'verification_expires_at' => $expiresAt->toIso8601String(),
        ], config('mail.default') === 'log' ? 503 : 202);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'email_verified' => $user->hasVerifiedEmail(),
                'email_verification_required' => (bool) config('auth.email_verification_enabled'),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
