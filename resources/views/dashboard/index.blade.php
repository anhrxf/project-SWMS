@extends('layouts.app')

@section('title', 'Dashboard Statistik')
@section('page_title', 'Dashboard Statistik Laporan Sampah')
@section('page_subtitle', $isAdmin ? 'Ringkasan operasional dan analisis pengelolaan sampah cerdas seluruh wilayah' : 'Ringkasan laporan sampah dan kontribusi lingkungan Anda')

@section('content')
<div class="space-y-6">

    <!-- Operational Status Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                {{ $isAdmin ? 'Panel Pengawasan Sampah Wilayah' : 'Laporan Saya' }}
            </h1>
            <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
                {{ $isAdmin 
                    ? 'Pantau laporan timbunan sampah, verifikasi bukti foto, dan tugaskan armada penanganan di lapangan.' 
                    : 'Laporkan tumpukan sampah di lingkungan Anda dengan foto dan koordinat lokasi untuk ditindaklanjuti petugas.' }}
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-shrink-0">
            @if(!$isAdmin)
            <a href="{{ route('reports.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                <i class="fa-solid fa-camera"></i> Buat laporan
            </a>
            @endif
            <a href="{{ route('ai.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition">
                <i class="fa-solid fa-microchip text-slate-500"></i> Uji model ML
            </a>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Total Reports Card -->
        <div class="col-span-2 lg:col-span-1 p-4 rounded-xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total laporan</span>
                <span class="text-slate-400 text-xs"><i class="fa-solid fa-folder-open"></i></span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($totalReports) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi keseluruhan</p>
            </div>
        </div>

        <!-- Pending Card -->
        <div class="p-4 rounded-xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-amber-700">Menunggu</span>
                <span class="text-amber-500 text-xs"><i class="fa-solid fa-hourglass-start"></i></span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-amber-700">{{ number_format($pendingReports) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Perlu diverifikasi</p>
            </div>
        </div>

        <!-- In Progress Card -->
        <div class="p-4 rounded-xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-sky-700">Diproses</span>
                <span class="text-sky-500 text-xs"><i class="fa-solid fa-truck-fast"></i></span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-sky-700">{{ number_format($inProgressReports) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Armada ditugaskan</p>
            </div>
        </div>

        <!-- Completed Card -->
        <div class="p-4 rounded-xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-emerald-700">Selesai</span>
                <span class="text-emerald-500 text-xs"><i class="fa-solid fa-check-double"></i></span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-emerald-700">{{ number_format($completedReports) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Sudah dibersihkan</p>
            </div>
        </div>

        <!-- Rejected Card -->
        <div class="p-4 rounded-xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-rose-700">Ditolak</span>
                <span class="text-rose-500 text-xs"><i class="fa-solid fa-ban"></i></span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-rose-700">{{ number_format($rejectedReports) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Data tidak sesuai</p>
            </div>
        </div>
    </div>

    <!-- Extra System Metrics for Admin -->
    @if($isAdmin)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500">Pengguna terdaftar</span>
                <div class="text-lg font-bold text-slate-900">{{ number_format($totalUsers) }} akun</div>
                <span class="text-[11px] text-slate-400">{{ $totalRegularUsers }} warga</span>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500">Rasio penyelesaian</span>
                <div class="text-lg font-bold text-emerald-700">{{ $resolutionRate }}%</div>
                <span class="text-[11px] text-slate-400">Dari total laporan masuk</span>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500">Akurasi rata-rata model</span>
                <div class="text-lg font-bold text-slate-900">{{ $avgConfidence }}%</div>
                <span class="text-[11px] text-slate-400">Tingkat keyakinan deteksi</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Interactive Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Trend Bar Chart (2 cols) -->
        <div class="lg:col-span-2 p-5 rounded-2xl bg-white border border-slate-200/80">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Aktivitas Laporan Masuk</h3>
                    <p class="text-xs text-slate-500">Volume harian selama 7 hari terakhir</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Waste Category Doughnut Chart (1 col) -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Komposisi Kategori</h3>
                        <p class="text-xs text-slate-500">Proporsi jenis sampah yang dilaporkan</p>
                    </div>
                </div>
                <div class="h-48 relative flex items-center justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs pt-3 border-t border-slate-100">
                <div>
                    <span class="font-bold text-slate-800">{{ $categoriesCount['organic'] }}</span>
                    <p class="text-[11px] text-slate-500">Organik</p>
                </div>
                <div>
                    <span class="font-bold text-slate-800">{{ $categoriesCount['plastic'] }}</span>
                    <p class="text-[11px] text-slate-500">Plastik</p>
                </div>
                <div>
                    <span class="font-bold text-slate-800">{{ $categoriesCount['paper'] }}</span>
                    <p class="text-[11px] text-slate-500">Kertas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Laporan Terbaru</h3>
                <p class="text-xs text-slate-500">Laporan yang baru masuk dan membutuhkan tindak lanjut</p>
            </div>
            <a href="{{ route('reports.index') }}" 
               class="text-xs font-medium text-emerald-700 hover:underline">
                Lihat semua laporan &rarr;
            </a>
        </div>

        @if($recentReports->isEmpty())
            <div class="p-12 text-center">
                <h4 class="text-sm font-medium text-slate-700">Belum ada laporan terdaftar</h4>
                <p class="text-xs text-slate-400 mt-1">{{ $isAdmin ? 'Laporan dari warga akan ditampilkan di sini saat ada yang masuk.' : 'Anda belum pernah mengirimkan laporan sampah.' }}</p>
                @if(!$isAdmin)
                <a href="{{ route('reports.create') }}" class="mt-3 inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition">
                    Buat laporan
                </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-medium text-[11px] border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3">Laporan</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Pelapor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-5 py-3 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($recentReports as $rep)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $rep->image_url }}" 
                                         alt="{{ $rep->title }}" 
                                         class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-slate-100 flex-shrink-0"
                                         onerror="this.src='https://placehold.co/100x100?text=Foto';">
                                    <div>
                                        <a href="{{ route('reports.show', $rep) }}" class="font-semibold text-slate-900 hover:text-emerald-700 line-clamp-1">
                                            {{ $rep->title }}
                                        </a>
                                        <p class="text-[11px] text-slate-400 line-clamp-1">{{ $rep->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium border {{ $rep->category_badge }}">
                                    {{ $rep->category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-[11px] text-slate-600 truncate max-w-[170px] block" title="{{ $rep->location_address }}">
                                    {{ $rep->location_address ?: 'GPS: ' . round($rep->latitude, 3) . ', ' . round($rep->longitude, 3) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-slate-700">{{ $rep->user?->name ?? 'Anonim' }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-medium border {{ $rep->status_badge }}">
                                    {{ $rep->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-400 text-[11px] whitespace-nowrap">
                                {{ $rep->created_at->diffForHumans() }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('reports.show', $rep) }}" 
                                   class="text-xs font-medium text-emerald-700 hover:underline">
                                    Buka
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Trend Bar Chart
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartDates) !!},
                    datasets: [{
                        label: 'Laporan Masuk',
                        data: {!! json_encode($chartCounts) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 8,
                        hoverBackgroundColor: '#059669',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { size: 11 } },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { font: { size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Category Doughnut Chart
        const catCtx = document.getElementById('categoryChart');
        if (catCtx) {
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Organik', 'Plastik', 'Kertas', 'Lainnya'],
                    datasets: [{
                        data: [
                            {{ $categoriesCount['organic'] }},
                            {{ $categoriesCount['plastic'] }},
                            {{ $categoriesCount['paper'] }},
                            {{ $categoriesCount['other'] }}
                        ],
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                        }
                    },
                    cutout: '72%',
                }
            });
        }
    });
</script>
@endpush
@endsection
