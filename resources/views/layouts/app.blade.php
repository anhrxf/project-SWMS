<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Smart Waste Management System</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <!-- Leaflet Maps CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
    @stack('styles')
</head>
<body class="h-full text-slate-800 antialiased" x-data="{ sidebarOpen: window.innerWidth >= 1024 }">

    <div class="min-h-full flex">
        <!-- Sidebar Mobile Overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"
             @click="sidebarOpen = false"
             x-cloak></div>

        <!-- Sidebar Navigation (Sticky/Fixed on scroll, collapsible) -->
        <aside :class="sidebarOpen ? 'w-64 translate-x-0' : '-translate-x-full lg:w-0 lg:border-r-0 lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-40 h-screen bg-slate-900 text-slate-100 transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:flex lg:flex-col border-r border-slate-800 shrink-0 overflow-hidden">
            
            <div class="w-64 h-full flex flex-col">
                <!-- Brand Header with close button (X khusus untuk menutup sidebar) -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800 shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white text-sm font-bold">
                            <i class="fa-solid fa-recycle"></i>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-white tracking-tight">SWMS Platform</span>
                            <p class="text-[11px] text-slate-400">Pengelolaan Sampah Terpadu</p>
                        </div>
                    </a>
                    <button @click="sidebarOpen = false" 
                            class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition flex items-center justify-center" 
                            title="Tutup Sidebar">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Navigation Links (Self-scrollable when content exceeds viewport) -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar text-xs">
                    <p class="px-3 pt-1 pb-1 text-[11px] font-medium text-slate-400">Menu</p>

                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-chart-pie w-4 text-center {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Ringkasan Statistik</span>
                    </a>

                    <a href="{{ route('reports.index') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('reports.index') || request()->routeIs('reports.show') || request()->routeIs('reports.edit') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-clipboard-list w-4 text-center {{ request()->routeIs('reports.*') && !request()->routeIs('reports.create') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Daftar Laporan</span>
                    </a>

                    @if(!auth()->user()->isAdmin())
                    <a href="{{ route('reports.create') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('reports.create') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-circle-plus w-4 text-center {{ request()->routeIs('reports.create') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Buat Laporan</span>
                    </a>
                    @endif

                    <p class="px-3 pt-4 pb-1 text-[11px] font-medium text-slate-400">Modul Machine Learning</p>

                    <a href="{{ route('ai.index') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('ai.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-microchip w-4 text-center {{ request()->routeIs('ai.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Klasifikasi Citra Sampah</span>
                    </a>

                    @if(auth()->user()->isAdmin())
                    <p class="px-3 pt-4 pb-1 text-[11px] font-medium text-slate-400">Manajemen</p>

                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('users.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-users w-4 text-center {{ request()->routeIs('users.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Pengguna Terdaftar</span>
                    </a>
                    @endif

                    <p class="px-3 pt-4 pb-1 text-[11px] font-medium text-slate-400">Sistem</p>

                    <a href="{{ route('docs.api') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('docs.api') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-book w-4 text-center {{ request()->routeIs('docs.api') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Spesifikasi API</span>
                    </a>

                    <a href="{{ route('profile') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('profile') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-user-gear w-4 text-center {{ request()->routeIs('profile') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- Logout Footer -->
                <div class="p-3 border-t border-slate-800 shrink-0">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-700/80 transition">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50">
            <!-- Top Navbar -->
            <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-8 py-3.5 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <!-- Tombol Garis 3 Khusus untuk MEMBUKA Sidebar -->
                    <button x-show="!sidebarOpen" 
                            @click="sidebarOpen = true" 
                            class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition flex items-center justify-center" 
                            title="Buka Sidebar"
                            x-cloak>
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight">@yield('page_title', 'Smart Waste Management')</h2>
                        <p class="text-xs text-slate-500 hidden sm:block">@yield('page_subtitle', 'Sistem Pengelolaan Sampah Cerdas Berbasis AI & GIS')</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition">
                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="text-xs font-semibold text-slate-700 hidden md:inline-block">{{ auth()->user()->name }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                        </button>

                        <div x-show="open" 
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50"
                             x-cloak>
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-user-pen text-slate-400 w-4"></i> Pengaturan Profil
                            </a>
                            <a href="{{ route('docs.api') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-code text-slate-400 w-4"></i> Dokumentasi API
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 text-left">
                                    <i class="fa-solid fa-arrow-right-from-bracket text-rose-500 w-4"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Floating Overlay Notifications (Auto-dismiss after 5s) -->
            <div class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-md w-[calc(100%-2.5rem)] sm:w-auto sm:min-w-[380px] pointer-events-none">
                @if(session('success'))
                    <div x-data="{ show: true }" 
                         x-init="setTimeout(() => show = false, 5000)" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         class="pointer-events-auto flex items-start gap-3 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-emerald-200 text-slate-800 shadow-2xl shadow-slate-900/10"
                         x-cloak>
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 text-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-xs font-semibold text-slate-900 leading-relaxed">{{ session('success') }}</p>
                            <div class="w-full bg-slate-100 h-1 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-[5000ms] ease-linear w-full"
                                     x-init="$nextTick(() => { $el.style.width = '0%' })"></div>
                            </div>
                        </div>
                        <button @click="show = false" class="text-slate-400 hover:text-slate-600 p-1 flex-shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" 
                         x-init="setTimeout(() => show = false, 5000)" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         class="pointer-events-auto flex items-start gap-3 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-rose-200 text-slate-800 shadow-2xl shadow-slate-900/10"
                         x-cloak>
                        <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0 text-sm">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-xs font-semibold text-slate-900 leading-relaxed">{{ session('error') }}</p>
                            <div class="w-full bg-slate-100 h-1 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-rose-500 h-full rounded-full transition-all duration-[5000ms] ease-linear w-full"
                                     x-init="$nextTick(() => { $el.style.width = '0%' })"></div>
                            </div>
                        </div>
                        <button @click="show = false" class="text-slate-400 hover:text-slate-600 p-1 flex-shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div x-data="{ show: true }" 
                         x-init="setTimeout(() => show = false, 5000)" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         class="pointer-events-auto flex items-start gap-3 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-sky-200 text-slate-800 shadow-2xl shadow-slate-900/10"
                         x-cloak>
                        <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center flex-shrink-0 text-sm">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-xs font-semibold text-slate-900 leading-relaxed">{{ session('info') }}</p>
                            <div class="w-full bg-slate-100 h-1 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-sky-500 h-full rounded-full transition-all duration-[5000ms] ease-linear w-full"
                                     x-init="$nextTick(() => { $el.style.width = '0%' })"></div>
                            </div>
                        </div>
                        <button @click="show = false" class="text-slate-400 hover:text-slate-600 p-1 flex-shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div x-data="{ show: true }" 
                         x-init="setTimeout(() => show = false, 5000)" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         class="pointer-events-auto p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-rose-200 text-slate-800 shadow-2xl shadow-slate-900/10"
                         x-cloak>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0 text-sm">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div class="flex-1 pt-0.5">
                                <p class="text-xs font-bold text-rose-900 mb-1">Terjadi kesalahan input:</p>
                                <ul class="list-disc list-inside text-xs space-y-0.5 text-slate-600">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button @click="show = false" class="text-slate-400 hover:text-slate-600 p-1 flex-shrink-0">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>
                        <div class="w-full bg-slate-100 h-1 rounded-full mt-3 overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full transition-all duration-[5000ms] ease-linear w-full"
                                 x-init="$nextTick(() => { $el.style.width = '0%' })"></div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main class="flex-1 px-4 sm:px-8 py-6">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="border-t border-slate-200/80 bg-white py-4 px-4 sm:px-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <div>
                    <span class="font-semibold text-slate-700">Smart Waste Management System (SWMS)</span> &copy; {{ date('Y') }} - Bilcode Technology Assessment
                </div>
                <div class="flex items-center gap-4 text-slate-400">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-bolt text-amber-500"></i> Laravel 11/13 + SQLite + Tailwind</span>
                    <a href="{{ route('docs.api') }}" class="hover:text-emerald-600 transition">API Docs</a>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
