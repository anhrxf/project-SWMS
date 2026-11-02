<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
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
                'verification_url' => URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(config('auth.verification.expire', 60)),
                    ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
                ),
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
                'message' => 'Kredensial tidak cocok dengan data kami.',
            ], 401);
        }

        $deviceName = $validated['device_name'] ?? 'swms_mobile_app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $user->hasVerifiedEmail()
                ? 'Login berhasil.'
                : 'Login berhasil, tetapi verifikasi email diperlukan sebelum menggunakan laporan.',
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

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat email sudah diverifikasi.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
        ], 202);
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
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
