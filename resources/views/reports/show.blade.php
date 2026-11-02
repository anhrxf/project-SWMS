@extends('layouts.app')

@section('title', 'Detail Laporan #' . $report->id)
@section('page_title', 'Detail Laporan Sampah')
@section('page_subtitle', 'Informasi menyeluruh, histori status, dan lokasi laporan #' . $report->id)

@section('content')
<div class="space-y-6" x-data="{ statusModal: false }">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('reports.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Laporan
        </a>

        <div class="flex items-center gap-2.5">
            @if($isAdmin)
                <!-- Admin Update Status Trigger -->
                <button type="button" 
                        @click="statusModal = true" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition">
                    <i class="fa-solid fa-pen-to-square"></i> Perbarui Status Laporan
                </button>
            @endif

            @if(!$isAdmin && $report->user_id === auth()->id() && $report->status === 'pending')
                <a href="{{ route('reports.edit', $report) }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    <i class="fa-solid fa-edit"></i> Edit
                </a>

                <form action="{{ route('reports.destroy', $report) }}" 
                      method="POST" 
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition">
                        <i class="fa-solid fa-trash-can"></i> Hapus
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Report Info, Photo, Map, AI Analysis -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card: Overview & Photo -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="relative h-72 sm:h-96 w-full bg-slate-900">
                    <img src="{{ $report->image_url }}" 
                         alt="{{ $report->title }}" 
                         class="w-full h-full object-cover"
                         onerror="this.src='https://placehold.co/800x500?text=Foto+Sampah';">
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex flex-col justify-end p-6 text-white">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md {{ $report->status_badge }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                {{ $report->status_label }}
                            </span>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md {{ $report->category_badge }}">
                                {{ $report->category_label }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">{{ $report->title }}</h1>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <h4 class="text-xs font-semibold text-slate-500 mb-1.5">Deskripsi laporan</h4>
                        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $report->description }}</p>
                    </div>

                    @if($report->admin_notes)
                    <div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 space-y-1">
                        <div class="flex items-center gap-1.5 text-xs font-bold">
                            <i class="fa-solid fa-clipboard-user text-purple-600"></i> Catatan Penanganan Administrator:
                        </div>
                        <p class="text-xs text-purple-800 leading-relaxed">{{ $report->admin_notes }}</p>
                    </div>
                    @endif

                    <!-- Location & Interactive Map -->
                    <div class="space-y-3 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-map-location-dot text-emerald-600"></i> Lokasi Sampah
                            </h4>
                            <span class="text-xs text-slate-500">{{ $report->location_address ?: 'Koordinat GPS' }}</span>
                        </div>

                        <div id="detailMap" class="w-full h-64 rounded-2xl border border-slate-200 z-10"></div>
                        @if($report->latitude && $report->longitude)
                            <div class="text-[11px] text-slate-400 flex items-center justify-between">
                                <span>Latitude: {{ $report->latitude }}, Longitude: {{ $report->longitude }}</span>
                                <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}" 
                                   target="_blank" 
                                   class="text-emerald-600 hover:underline font-semibold">
                                    Buka di Google Maps <i class="fa-solid fa-external-link text-[10px]"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card: AI Waste Detection & Recommendation -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Klasifikasi Citra Sampah</h3>
                        <p class="text-[11px] text-slate-500">Hasil analisis model visual terhadap foto laporan</p>
                    </div>
                    @if($report->ai_confidence !== null)
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                            {{ number_format((float) $report->ai_confidence, 2) }}% keyakinan
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600">
                            Belum dianalisis
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[11px] text-slate-500">
                            {{ $report->ai_detected_category ? 'Kategori hasil deteksi:' : 'Kategori laporan:' }}
                        </span>
                        <div class="text-sm font-bold text-slate-900 mt-0.5 capitalize">
                            {{ $report->ai_detected_category ?: $report->waste_category }}
                        </div>
                        @if($report->ai_confidence !== null)
                            <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2 overflow-hidden" role="progressbar" aria-label="Keyakinan model" aria-valuenow="{{ $report->ai_confidence }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $report->ai_confidence }}%"></div>
                            </div>
                        @else
                            <p class="text-[11px] text-slate-500 mt-2">Hasil model tidak tersedia untuk laporan ini.</p>
                        @endif
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[11px] text-slate-500">Kanal pengolahan:</span>
                        <div class="text-sm font-bold text-slate-900 mt-0.5">
                            {{ $recommendation['type'] ?? 'Daur Ulang Terpadu' }}
                        </div>
                        <div class="text-[11px] text-slate-600 mt-1">
                            {{ $recommendation['bin'] ?? 'Tempat Sampah Terpisah' }}
                        </div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs leading-relaxed">
                    <span class="font-semibold text-slate-800 block mb-0.5">Panduan penanganan:</span>
                    <p class="text-slate-600">{{ $recommendation['action'] ?? 'Pilah dan salurkan ke bank sampah setempat.' }}</p>
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Reporter Info & Status History Timeline -->
        <div class="space-y-6">

            <!-- Card: Reporter & Meta Info -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-slate-900 border-b border-slate-100 pb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i> Informasi Pelapor
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nama Pelapor:</span>
                        <span class="font-semibold text-slate-800">{{ $report->user?->name ?? 'Anonim' }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">Email Pelapor:</span>
                        <span class="font-medium text-slate-700">{{ $report->user?->email ?? '-' }}</span>
                    </div>

                    @if($report->user?->phone)
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nomor Telepon:</span>
                        <span class="font-medium text-slate-700">{{ $report->user->phone }}</span>
                    </div>
                    @endif

                    <div class="border-t border-slate-100 pt-2">
                        <span class="text-slate-400 block text-[11px]">Waktu Pelaporan:</span>
                        <span class="font-medium text-slate-700">{{ $report->created_at->format('d F Y, H:i') }} WIB</span>
                    </div>

                    @if($report->resolved_at)
                    <div>
                        <span class="text-emerald-700 block text-[11px] font-bold">Diselesaikan Pada:</span>
                        <span class="font-bold text-emerald-800">{{ $report->resolved_at->format('d F Y, H:i') }} WIB</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Card: Status Logs Timeline -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-sky-600"></i> Riwayat Status Penanganan
                </h3>

                <div class="flow-root">
                    <ul class="-mb-6">
                        @foreach($report->statusLogs as $index => $log)
                        <li>
                            <div class="relative pb-6">
                                @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-4 ring-white
                                            {{ $log->new_status === 'completed' ? 'bg-emerald-500 text-white' : 
                                               ($log->new_status === 'in_progress' ? 'bg-sky-500 text-white' : 
                                               ($log->new_status === 'rejected' ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white')) }}">
                                            <i class="fa-solid 
                                                {{ $log->new_status === 'completed' ? 'fa-check' : 
                                                   ($log->new_status === 'in_progress' ? 'fa-truck-fast' : 
                                                   ($log->new_status === 'rejected' ? 'fa-xmark' : 'fa-hourglass-start')) }} text-xs"></i>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-0.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-900 capitalize">
                                                {{ ucfirst(str_replace('_', ' ', $log->new_status)) }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">{{ $log->notes }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Oleh: {{ $log->user?->name ?? 'Sistem' }}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>

    </div>

    <!-- Admin Status Modal -->
    @if($isAdmin)
    <div x-show="statusModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" 
         x-cloak>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4" 
             @click.outside="statusModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-purple-600"></i> Perbarui Status Laporan #{{ $report->id }}
                </h3>
                <button @click="statusModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('reports.update-status', $report) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status Baru</label>
                    <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">
                        <option value="pending" {{ $report->status === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                        <option value="in_progress" {{ $report->status === 'in_progress' ? 'selected' : '' }}>In Progress (Sedang Ditindaklanjuti)</option>
                        <option value="completed" {{ $report->status === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                        <option value="rejected" {{ $report->status === 'rejected' ? 'selected' : '' }}>Rejected (Ditolak / Tidak Valid)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tindak Lanjut / Alasan</label>
                    <textarea name="admin_notes" 
                              rows="3" 
                              placeholder="Contoh: Petugas kebersihan armada 2 telah membersihkan lokasi pada pk 14:00..."
                              class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-purple-200 focus:border-purple-500 focus:outline-none transition">{{ $report->admin_notes }}</textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="statusModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/30 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
    @if(session('clear_report_draft'))
    const draftRequest = indexedDB.open('swms-report-drafts', 1);
    draftRequest.onsuccess = () => {
        const draftDb = draftRequest.result;
        if (!draftDb.objectStoreNames.contains('drafts')) {
            draftDb.close();
            return;
        }

        const transaction = draftDb.transaction('drafts', 'readwrite');
        transaction.objectStore('drafts').delete('user-{{ auth()->id() }}');
        transaction.oncomplete = () => draftDb.close();
        transaction.onerror = () => {
            console.error('Draf laporan berhasil dikirim, tetapi salinan lokal tidak dapat dihapus:', transaction.error);
            draftDb.close();
        };
    };
    draftRequest.onerror = () => console.error('Draf laporan terkirim, tetapi penyimpanan lokal tidak dapat dibuka:', draftRequest.error);
    @endif

    document.addEventListener('DOMContentLoaded', () => {
        const lat = {{ $report->latitude ?: -6.2088 }};
        const lng = {{ $report->longitude ?: 106.8456 }};
        
        const map = L.map('detailMap').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<b>{{ addslashes($report->title) }}</b><br>{{ addslashes($report->location_address ?: 'Lokasi Sampah') }}`).openPopup();
    });
</script>
@endpush
@endsection
