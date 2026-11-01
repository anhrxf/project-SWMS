@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page_title', 'Manajemen Pengguna Sistem')
@section('page_subtitle', 'Kelola akun administrator dan masyarakat pengguna aplikasi')

@section('content')
<div class="space-y-6">

    <!-- Top Stats & Add User -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-700">
                Total: <span class="font-bold text-slate-900">{{ $stats['total'] }}</span>
            </span>
            <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-purple-50 border border-purple-200 text-purple-700">
                Admin: <span class="font-bold text-purple-900">{{ $stats['admins'] }}</span>
            </span>
            <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700">
                Warga/User: <span class="font-bold text-emerald-900">{{ $stats['users'] }}</span>
            </span>
        </div>

        <a href="{{ route('users.create') }}" 
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/30 transition self-start sm:self-auto">
            <i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Search -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Cari pengguna</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari nama, email, atau nomor telepon..." 
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Role Filter & Submit -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Peran akun</label>
                    <select name="role" class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">
                        <option value="">Semua peran</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Warga</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                    <i class="fa-solid fa-filter"></i>
                </button>
                @if(request()->anyFilled(['search', 'role']))
                    <a href="{{ route('users.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-medium text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">Jumlah Laporan</th>
                        <th class="px-4 py-3">Terdaftar</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full {{ $u->isAdmin() ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200' }} border flex items-center justify-center font-bold text-sm uppercase">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            @if($u->isAdmin())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    <i class="fa-solid fa-shield-halved text-[10px]"></i> Administrator
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-user text-[10px]"></i> Warga
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-slate-600 font-medium">{{ $u->phone ?: '-' }}</div>
                            <div class="text-[11px] text-slate-400 truncate max-w-[150px]">{{ $u->address ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                <i class="fa-solid fa-file-lines text-slate-400 text-[10px]"></i> {{ $u->reports_count }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-400 text-[11px] whitespace-nowrap">
                            {{ $u->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $u) }}" 
                                   class="p-2 rounded-lg bg-slate-100 hover:bg-purple-50 hover:text-purple-700 text-slate-600 transition"
                                   title="Edit Pengguna">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                @if($u->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $u) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $u->name }}? Seluruh laporan terkait juga akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-600 transition"
                                                title="Hapus Pengguna">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            Tidak ada pengguna ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>

</div>
@endsection
