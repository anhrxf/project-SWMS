@extends('layouts.app')

@section('title', 'Manajemen Laporan Sampah')
@section('page_title', 'Daftar Laporan Sampah')
@section('page_subtitle', 'Kelola, filter, dan telusuri seluruh laporan kondisi sampah')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar & Status Filter Pills -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 {{ !request('status') || request('status') === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                <span>Semua</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ !request('status') || request('status') === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $statusCounts['all'] }}</span>
            </a>

            <a href="{{ route('reports.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 {{ request('status') === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-amber-700 border border-amber-200 hover:bg-amber-50' }}">
                <span>Menunggu (Pending)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('status') === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $statusCounts['pending'] }}</span>
            </a>

            <a href="{{ route('reports.index', array_merge(request()->except('status', 'page'), ['status' => 'in_progress'])) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 {{ request('status') === 'in_progress' ? 'bg-sky-600 text-white shadow-xs' : 'bg-white text-sky-700 border border-sky-200 hover:bg-sky-50' }}">
                <span>Diproses (In Progress)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('status') === 'in_progress' ? 'bg-white/20 text-white' : 'bg-sky-100 text-sky-800' }}">{{ $statusCounts['in_progress'] }}</span>
            </a>

            <a href="{{ route('reports.index', array_merge(request()->except('status', 'page'), ['status' => 'completed'])) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 {{ request('status') === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50' }}">
                <span>Selesai (Completed)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('status') === 'completed' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800' }}">{{ $statusCounts['completed'] }}</span>
            </a>

            <a href="{{ route('reports.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 {{ request('status') === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-rose-700 border border-rose-200 hover:bg-rose-50' }}">
                <span>Ditolak (Rejected)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('status') === 'rejected' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800' }}">{{ $statusCounts['rejected'] }}</span>
            </a>
        </div>

        @if(!$isAdmin)
        <a href="{{ route('reports.create') }}" 
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition self-start md:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Buat Laporan Baru</span>
        </a>
        @endif
    </div>

    <!-- Filter and Search Toolbar -->
    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Cari laporan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari judul, lokasi, atau deskripsi..." 
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Filter Category -->
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kategori sampah</label>
                <select name="category" class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                    <option value="all">Semua kategori</option>
                    <option value="organic" {{ request('category') === 'organic' ? 'selected' : '' }}>Organik</option>
                    <option value="plastic" {{ request('category') === 'plastic' ? 'selected' : '' }}>Plastik</option>
                    <option value="paper" {{ request('category') === 'paper' ? 'selected' : '' }}>Kertas</option>
                    <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Dari tanggal</label>
                <input type="date" 
                       name="date_from" 
                       value="{{ request('date_from') }}" 
                       class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
            </div>

            <!-- Date To & Submit Button -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sampai tanggal</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}" 
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                </div>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition flex items-center justify-center">
                    <i class="fa-solid fa-filter"></i>
                </button>
                @if(request()->anyFilled(['search', 'category', 'date_from', 'date_to', 'status']))
                    <a href="{{ route('reports.index') }}" 
                       title="Reset Filter"
                       class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition flex items-center justify-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Reports Display (Grid of Cards) -->
    @if($reports->isEmpty())
        <div class="p-16 rounded-3xl bg-white border border-slate-200 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-900">Tidak Ada Laporan Ditemukan</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Tidak ada laporan yang sesuai dengan filter atau kata kunci pencarian Anda.
            </p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('reports.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                    Reset Filter
                </a>
                @if(!$isAdmin)
                <a href="{{ route('reports.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition">
                    Buat Laporan Baru
                </a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($reports as $rep)
            <div class="group bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition duration-200 flex flex-col overflow-hidden">
                <!-- Card Image Header -->
                <div class="relative h-48 w-full bg-slate-100 overflow-hidden">
                    <img src="{{ $rep->image_url }}" 
                         alt="{{ $rep->title }}" 
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                         onerror="this.src='https://placehold.co/400x250?text=Foto+Sampah';">
                    
                    <!-- Status Badge Floating -->
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md shadow-xs {{ $rep->status_badge }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $rep->status_label }}
                        </span>
                    </div>

                    <!-- Category Badge Floating -->
                    <div class="absolute top-3 right-3">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md shadow-xs {{ $rep->category_badge }}">
                            {{ $rep->category_label }}
                        </span>
                    </div>

                    <!-- AI Confidence Badge -->
                    @if($rep->ai_confidence)
                        <div class="absolute bottom-3 left-3 px-2.5 py-1 rounded-lg bg-slate-900/80 backdrop-blur-md text-white text-[11px] font-semibold flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i>
                            <span>AI {{ $rep->ai_confidence }}%</span>
                        </div>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1.5">
                            <span><i class="fa-regular fa-clock mr-1"></i>{{ $rep->created_at->format('d M Y, H:i') }}</span>
                            <span class="font-medium text-slate-600"><i class="fa-regular fa-user mr-1"></i>{{ $rep->user?->name ?? 'Anonim' }}</span>
                        </div>

                        <a href="{{ route('reports.show', $rep) }}" class="block">
                            <h3 class="font-bold text-slate-900 text-sm line-clamp-1 hover:text-emerald-600 transition">
                                {{ $rep->title }}
                            </h3>
                        </a>

                        <p class="text-xs text-slate-500 line-clamp-2 mt-1.5 leading-relaxed">
                            {{ $rep->description }}
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div class="text-[11px] text-slate-500 truncate max-w-[170px]" title="{{ $rep->location_address }}">
                            <i class="fa-solid fa-location-dot text-rose-500 mr-1"></i>
                            {{ $rep->location_address ?: 'GPS: ' . round($rep->latitude, 3) . ', ' . round($rep->longitude, 3) }}
                        </div>

                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('reports.show', $rep) }}" 
                               class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-semibold transition">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination Links -->
        <div class="mt-6">
            {{ $reports->links() }}
        </div>
    @endif

</div>
@endsection
