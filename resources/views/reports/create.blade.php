@extends('layouts.app')

@section('title', 'Buat Laporan Sampah Baru')
@section('page_title', 'Form Pelaporan Sampah Baru')
@section('page_subtitle', 'Unggah foto sampah, sistem AI akan mendeteksi kategori secara otomatis beserta lokasi GPS')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="reportCreateComponent()">

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Hidden AI Results & Coordinates -->
        <input type="hidden" name="ai_detected_category" x-model="aiResult.category">
        <input type="hidden" name="ai_confidence" x-model="aiResult.confidence">
        <input type="hidden" name="latitude" x-model="latitude">
        <input type="hidden" name="longitude" x-model="longitude">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Col: Photo Upload & AI Detection Panel (1 col) -->
            <div class="space-y-4">
                <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                    <label class="block text-xs font-bold text-slate-800">
                        Foto Sampah <span class="text-rose-500">*</span>
                    </label>

                    <!-- Image Upload / Drag & Drop Zone -->
                    <div class="relative border-2 border-dashed rounded-2xl p-4 text-center cursor-pointer transition select-none group"
                         :class="isDragging ? 'border-emerald-500 bg-emerald-50/70 ring-4 ring-emerald-100 scale-[1.01]' : 'border-slate-300 hover:border-emerald-500 bg-slate-50/50'"
                         @dragover.prevent="isDragging = true"
                         @dragenter.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleFileDrop($event)"
                         @click="$refs.fileInput.click()">
                        
                        <input type="file" 
                               name="image" 
                               x-ref="fileInput" 
                               @change="handleImageUpload($event)" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               class="hidden">

                        <!-- Preview if image selected -->
                        <template x-if="imagePreview">
                            <div class="relative">
                                <img :src="imagePreview" alt="Preview Sampah" class="w-full h-52 object-cover rounded-xl shadow-xs">
                                <div class="absolute inset-0 bg-slate-900/40 rounded-xl opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-semibold gap-2">
                                    <span class="inline-flex items-center gap-1"><i class="fa-solid fa-rotate mr-1"></i> Klik / Seret Foto Baru</span>
                                </div>
                            </div>
                        </template>

                        <!-- Placeholder if no image -->
                        <template x-if="!imagePreview">
                            <div class="py-7 space-y-2">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto text-2xl transition"
                                     :class="isDragging ? 'bg-emerald-600 text-white scale-110' : 'bg-emerald-100 text-emerald-600 group-hover:scale-110'">
                                    <i class="fa-solid" :class="isDragging ? 'fa-arrow-down' : 'fa-cloud-arrow-up'"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-800" x-text="isDragging ? 'Lepaskan foto di sini...' : 'Klik atau seret foto ke sini'"></p>
                                <p class="text-[11px] text-slate-400">JPG, PNG, atau WEBP (Maksimal 10MB)</p>
                            </div>
                        </template>
                    </div>

                    @error('image')
                        <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror

                    <!-- Tombol Cepat Kamera Langsung -->
                    <button type="button" 
                            @click="openCamera()" 
                            class="w-full py-2.5 px-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-camera text-emerald-600"></i>
                        <span>Ambil Foto Langsung dari Kamera</span>
                    </button>

                    <!-- AI Detection Card Status -->
                    <div class="rounded-xl p-3.5 transition" 
                         :class="aiLoading ? 'bg-slate-50 border border-slate-200' : (aiResult.category ? 'bg-slate-50 border border-slate-200' : 'bg-slate-50 border border-dashed border-slate-200')">
                        
                        <!-- AI Loading Spinner -->
                        <div x-show="aiLoading" class="flex items-center gap-2.5 text-slate-700 py-1" x-cloak>
                            <i class="fa-solid fa-circle-notch fa-spin text-base text-emerald-600"></i>
                            <div>
                                <p class="text-xs font-semibold">Menganalisis citra sampah...</p>
                                <p class="text-[11px] text-slate-400">Memproses ekstraksi fitur dan klasifikasi</p>
                            </div>
                        </div>

                        <!-- AI Result Displayed -->
                        <div x-show="!aiLoading && aiResult.category" class="space-y-2.5" x-cloak>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-900">Hasil klasifikasi model</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                    <span x-text="aiResult.confidence + '%'"></span> keyakinan
                                </span>
                            </div>

                            <div class="p-3 bg-white rounded-lg border border-slate-200">
                                <p class="text-[11px] text-slate-500">Prediksi kategori:</p>
                                <h4 class="text-xs font-bold text-slate-900 mt-0.5" x-text="aiResult.label"></h4>

                                <!-- Confidence Progress Bar -->
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                    <div class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300" 
                                         :style="'width: ' + aiResult.confidence + '%'"></div>
                                </div>
                            </div>

                            <!-- Eco Advice Tip -->
                            <div class="text-[11px] text-slate-600 leading-relaxed bg-white p-2.5 rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800 block mb-0.5">Rekomendasi penanganan:</span>
                                <p x-text="aiResult.recommendation?.action"></p>
                            </div>
                        </div>

                        <!-- Initial Idle State -->
                        <div x-show="!aiLoading && !aiResult.category" class="text-center py-3 text-slate-400">
                            <p class="text-[11px]">Unggah foto untuk mendeteksi kategori sampah secara otomatis.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Report Details & Interactive Map (2 cols) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                    
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-800 mb-1">
                            Judul Laporan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               required 
                               value="{{ old('title') }}" 
                               placeholder="Contoh: Timbunan Botol Plastik di Pinggir Jalan Merdeka"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('title') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:ring-4 focus:border-emerald-500 focus:outline-none transition">
                        @error('title')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category Selector (Auto-updated by AI, user can override) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="waste_category" class="block text-xs font-bold text-slate-800">
                                Kategori Sampah <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-400">Otomatis terisi oleh AI</span>
                        </div>
                        <select id="waste_category" 
                                name="waste_category" 
                                x-model="selectedCategory"
                                required 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition bg-white">
                            <option value="">-- Pilih Kategori Sampah --</option>
                            <option value="organic">Sampah Organik (Sisa Makanan, Daun, Ranting, Kulit Buah)</option>
                            <option value="plastic">Sampah Plastik (Botol, Kantong Kresek, Cup Minuman)</option>
                            <option value="paper">Sampah Kertas & Karton (Kardus, Koran, Kertas HVS)</option>
                            <option value="other">Sampah Campuran / Residu Lainnya</option>
                        </select>
                        @error('waste_category')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-800 mb-1">
                            Deskripsi Kondisi Sampah <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3" 
                                  required 
                                  placeholder="Jelaskan kondisi tumpukan sampah, perkiraan volume, bau, atau akses jalan..."
                                  class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('description') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-200 focus:ring-emerald-200' }} text-xs font-medium focus:ring-4 focus:border-emerald-500 focus:outline-none transition">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Location Address -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="location_address" class="block text-xs font-bold text-slate-800">
                                Alamat / Patokan Lokasi
                            </label>
                            <button type="button" 
                                    @click="getCurrentLocation()" 
                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                                <i class="fa-solid fa-location-crosshairs"></i> Gunakan GPS Saya
                            </button>
                        </div>
                        <input type="text" 
                               id="location_address" 
                               name="location_address" 
                               x-model="addressText"
                               placeholder="Contoh: Jl. Diponegoro No. 45, Depan Taman Kota"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 focus:outline-none transition">
                    </div>

                    <!-- Interactive Map Picker -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                <i class="fa-solid fa-map-location-dot text-emerald-600 mr-1"></i> Titik Lokasi Peta (Geser Pin Marker)
                            </span>
                            <span class="text-[11px] text-slate-400" x-text="'Koordinat: ' + Number(latitude).toFixed(4) + ', ' + Number(longitude).toFixed(4)"></span>
                        </div>
                        <div id="mapPicker" class="w-full h-56 rounded-2xl border border-slate-200 z-10"></div>
                        <p class="text-[10px] text-slate-400 mt-1">
                            * Klik pada peta atau geser pin marker ke lokasi sampah yang ingin Anda laporkan.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('reports.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                            Batal
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Kirim laporan</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>

    <!-- Modal Kamera Langsung -->
    <div x-show="cameraOpen" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" 
         x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 p-4 sm:p-5 space-y-4"
             @click.outside="closeCamera()">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Kamera Siap Foto Langsung</h3>
                        <p class="text-[11px] text-slate-400">Arahkan kamera ke tumpukan sampah</p>
                    </div>
                </div>
                <button type="button" @click="closeCamera()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Video Viewfinder Container -->
            <div class="relative bg-slate-900 rounded-xl overflow-hidden aspect-[4/3] flex items-center justify-center border border-slate-800">
                <video x-ref="cameraVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <div class="absolute inset-4 border border-white/30 rounded-lg pointer-events-none flex items-center justify-center">
                    <span class="text-white/40 text-[10px] tracking-wider uppercase font-semibold">Area Bidik Foto</span>
                </div>
                
                <div x-show="cameraLoading" class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white gap-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-500"></i>
                    <span class="text-xs">Mengaktifkan kamera...</span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center justify-between pt-1">
                <button type="button" 
                        @click="closeCamera()" 
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    Tutup
                </button>
                <button type="button" 
                        @click="capturePhoto()" 
                        :disabled="cameraLoading"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                    <i class="fa-solid fa-camera"></i>
                    <span>Jepret & Gunakan Foto</span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function reportCreateComponent() {
        return {
            imagePreview: null,
            isDragging: false,
            cameraOpen: false,
            cameraLoading: false,
            cameraStream: null,
            aiLoading: false,
            aiResult: {
                category: '',
                confidence: null,
                label: '',
                recommendation: null,
            },
            selectedCategory: '{{ old('waste_category') }}',
            latitude: -6.2088,   // Default Jakarta center
            longitude: 106.8456,
            addressText: '{{ old('location_address') }}',
            map: null,
            marker: null,

            init() {
                // Prevent browser from opening files dropped anywhere on page
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    window.addEventListener(eventName, (e) => e.preventDefault(), false);
                });

                this.$nextTick(() => {
                    this.initMap();
                });
            },

            initMap() {
                this.map = L.map('mapPicker').setView([this.latitude, this.longitude], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);

                this.marker = L.marker([this.latitude, this.longitude], { draggable: true }).addTo(this.map);

                this.marker.on('dragend', (event) => {
                    const pos = event.target.getLatLng();
                    this.latitude = pos.lat;
                    this.longitude = pos.lng;
                    this.reverseGeocode(pos.lat, pos.lng);
                });

                this.map.on('click', (event) => {
                    const pos = event.latlng;
                    this.marker.setLatLng(pos);
                    this.latitude = pos.lat;
                    this.longitude = pos.lng;
                    this.reverseGeocode(pos.lat, pos.lng);
                });
            },

            getCurrentLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.latitude = position.coords.latitude;
                            this.longitude = position.coords.longitude;
                            if (this.map && this.marker) {
                                this.map.setView([this.latitude, this.longitude], 16);
                                this.marker.setLatLng([this.latitude, this.longitude]);
                            }
                            this.reverseGeocode(this.latitude, this.longitude);
                        },
                        (error) => {
                            alert('Tidak dapat mendeteksi lokasi GPS secara otomatis: ' + error.message);
                        }
                    );
                } else {
                    alert('Browser Anda tidak mendukung Geolocation.');
                }
            },

            reverseGeocode(lat, lng) {
                if (!this.addressText) {
                    this.addressText = `Koordinat: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                }
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer ? event.dataTransfer.files : null;
                if (!files || !files.length) return;
                const file = files[0];
                if (!file.type.startsWith('image/')) {
                    alert('File yang diunggah harus berupa gambar (JPG, PNG, atau WEBP).');
                    return;
                }

                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.fileInput.files = dt.files;
                } catch (e) {
                    // Fallback browser lama
                }

                this.processFile(file);
            },

            handleImageUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.processFile(file);
            },

            processFile(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);

                this.runAiDetection(file);
            },

            async openCamera() {
                this.cameraOpen = true;
                this.cameraLoading = true;

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    });
                    this.cameraStream = stream;
                    this.$nextTick(() => {
                        if (this.$refs.cameraVideo) {
                            this.$refs.cameraVideo.srcObject = stream;
                            this.$refs.cameraVideo.play();
                        }
                    });
                } catch (err) {
                    alert('Tidak dapat mengaktifkan kamera: ' + (err.message || 'Izin akses kamera ditolak. Pastikan izin kamera aktif pada browser Anda.'));
                    this.closeCamera();
                } finally {
                    this.cameraLoading = false;
                }
            },

            closeCamera() {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(track => track.stop());
                    this.cameraStream = null;
                }
                this.cameraOpen = false;
                this.cameraLoading = false;
            },

            capturePhoto() {
                const video = this.$refs.cameraVideo;
                if (!video || !video.videoWidth) {
                    alert('Kamera belum siap.');
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    if (!blob) return;
                    const file = new File([blob], 'foto_sampah_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        this.$refs.fileInput.files = dt.files;
                    } catch (e) {
                        // Fallback browser lama
                    }

                    this.processFile(file);
                    this.closeCamera();
                }, 'image/jpeg', 0.92);
            },

            async runAiDetection(file) {
                this.aiLoading = true;
                const formData = new FormData();
                formData.append('image', file);

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch('{{ route('ai.classify') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const json = await res.json();
                    if (json.success && json.data) {
                        this.aiResult = json.data;
                        this.selectedCategory = json.data.category;
                    }
                } catch (err) {
                    console.error('AI Error:', err);
                } finally {
                    this.aiLoading = false;
                }
            }
        };
    }
</script>
@endpush
@endsection
