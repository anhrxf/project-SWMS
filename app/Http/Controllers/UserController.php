<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount('reports')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->input('role'), ['admin', 'user'])) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'users' => User::where('role', 'user')->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $emailRule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', $emailRule, 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:admin,user'],
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

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $emailRule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', $emailRule, 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'in:admin,user'],
            'phone' => ['required', 'string', 'min:9', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
            'email.email' => 'Email tidak valid.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.unique' => 'Nomor telah terdaftar.',
            'phone.min' => 'Nomor HP minimal terdiri dari 9 digit angka.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}
