<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') - Smart Waste Management System</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-xl shadow-emerald-500/30 group-hover:scale-105 transition">
                <i class="fa-solid fa-recycle text-2xl"></i>
            </div>
            <div class="text-left">
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                    SWMS <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold border border-emerald-500/30">AI Edition</span>
                </h1>
                <p class="text-xs text-slate-400">Smart Waste Management System</p>
            </div>
        </a>
    </div>

    <!-- Floating Overlay Notifications (Auto-dismiss after 5s) -->
    <div class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-md w-[calc(100%-2.5rem)] sm:w-auto sm:min-w-[360px] pointer-events-none">
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
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white/95 backdrop-blur-xl py-8 px-6 shadow-2xl sm:rounded-3xl sm:px-10 border border-white/20">
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Smart Waste Management System &copy; {{ date('Y') }} &bull; Bilcode Technology Test
        </p>
    </div>

</body>
</html>
