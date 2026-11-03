@extends('layouts.app')

@section('title', 'AI Waste Detection Lab')
@section('page_title', 'AI Waste Classification Lab')
@section('page_subtitle', 'Pengujian model deteksi klasifikasi sampah cerdas (Organik, Plastik, Kertas) berbasis Machine Learning')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="aiLabComponent()">

    <!-- Header Description Card -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-1.5 max-w-xl">
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Evaluasi Model Klasifikasi Citra Sampah</h2>
            <p class="text-xs text-slate-600 leading-relaxed">
                Model PyTorch MobileNetV3 Small hanya membedakan sampah organik, plastik, dan kertas. Prediksi diterima bila dua tampilan sepakat dan confidence minimal 70%; selain itu AI tidak menetapkan kelas. Dataset kecil, sehingga hasil bukan jaminan untuk kondisi nyata atau objek di luar tiga kelas.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 text-center w-full md:w-auto flex-shrink-0">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xl font-bold text-slate-900">
                    {{ $evaluation ? number_format($evaluation['test_accuracy'] * 100, 2) . '%' : '—' }}
                </div>
                <div class="text-[11px] text-slate-500">Akurasi pengujian</div>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xl font-bold text-slate-900">
                    {{ $evaluation ? array_sum($evaluation['unique_images_per_class']) : '—' }}
                </div>
                <div class="text-[11px] text-slate-500">Total dataset sampel</div>
            </div>
            @if($evaluation && isset($evaluation['selective_classification']))
                <div class="col-span-2 p-3.5 rounded-xl bg-amber-50 border border-amber-200">
                    <div class="text-sm font-bold text-amber-900">
                        {{ $evaluation['selective_classification']['accepted'] }}/{{ $evaluation['split_sizes']['test'] }}
                        foto diterima ·
                        {{ number_format($evaluation['selective_classification']['coverage'] * 100, 1) }}% coverage
                    </div>
                    <div class="text-[11px] text-amber-800">Ambang confidence 70% dan dua tampilan harus sepakat; foto lain perlu kategori manual.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Testing Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Left: Upload Box & Samples -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i> Unggah Gambar Pengujian
            </h3>

            <!-- Dropzone -->
            <div class="relative border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-6 text-center cursor-pointer transition bg-slate-50/60 group"
                 @click="$refs.labFileInput.click()">
                
                <input type="file" 
                       x-ref="labFileInput" 
                       @change="onFileSelected($event)" 
                       accept="image/jpeg,image/png,image/jpg,image/webp" 
                       class="hidden">

                <template x-if="imagePreview">
                    <div class="space-y-2">
                        <img :src="imagePreview" alt="Preview Gambar" class="w-full h-64 object-cover rounded-xl shadow-xs">
                        <p class="text-[11px] text-slate-500 font-medium group-hover:text-emerald-600">Klik untuk memilih gambar lain</p>
                    </div>
                </template>

                <template x-if="!imagePreview">
                    <div class="py-12 space-y-2">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-3xl group-hover:scale-110 transition">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">Pilih atau seret gambar sampah ke sini</h4>
                        <p class="text-xs text-slate-400">Format JPG, PNG, atau WEBP</p>
                    </div>
                </template>
            </div>

            <!-- Pre-made Sample Prompts -->
            <div class="pt-2">
                <span class="block text-xs font-medium text-slate-500 mb-2">Sampel uji cepat:</span>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" 
                            @click="loadSample('organic')" 
                            class="p-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium text-center transition">
                        Sampah Organik
                    </button>
                    <button type="button" 
                            @click="loadSample('plastic')" 
                            class="p-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium text-center transition">
                        Botol Plastik
                    </button>
                    <button type="button" 
                            @click="loadSample('paper')" 
                            class="p-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium text-center transition">
                        Kardus Kertas
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: AI Result & Classification Analysis -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Hasil Prediksi Model</h3>
                    <template x-if="result.category">
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                            <span x-text="result.confidence + '%'"></span> keyakinan
                        </span>
                    </template>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="py-16 text-center space-y-2" x-cloak>
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-600"></i>
                    <p class="text-xs font-semibold text-slate-700">Mengekstraksi fitur visual...</p>
                    <p class="text-[11px] text-slate-400">Menghitung probabilitas kelas</p>
                </div>

                <!-- Idle State -->
                <div x-show="!loading && error" class="py-10 text-center text-rose-700 space-y-2" role="alert" x-cloak>
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    <p class="text-xs font-semibold">Deteksi AI gagal.</p>
                    <p class="text-[11px]" x-text="error"></p>
                </div>

                <div x-show="!loading && uncertain" class="py-10 text-center text-amber-800 space-y-2" role="status" x-cloak>
                    <i class="fa-solid fa-circle-question text-xl"></i>
                    <p class="text-xs font-semibold">AI belum cukup yakin untuk menetapkan kategori.</p>
                    <p class="text-[11px]" x-text="message"></p>
                </div>

                <div x-show="!loading && !result.category && !error && !uncertain" class="py-16 text-center text-slate-400 space-y-1">
                    <p class="text-xs">Pilih atau unggah gambar untuk memulai pengujian.</p>
                </div>

                <!-- Result Content -->
                <div x-show="!loading && result.category" class="space-y-4 pt-2" x-cloak>
                    
                    <!-- Top Category Card -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[11px] text-slate-500">Prediksi kelas:</span>
                        <div class="text-base font-bold text-slate-900 capitalize mt-0.5" x-text="result.label"></div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Engine: <span class="font-medium text-slate-700" x-text="result.source === 'fastapi_ml_model' ? 'Microservice Neural Network' : 'Computer Vision Classifier'"></span>
                        </div>
                    </div>

                    <!-- Breakdown Probabilities -->
                    <div class="space-y-2">
                        <span class="text-xs font-semibold text-slate-700 block">Distribusi probabilitas:</span>

                        <!-- Organic Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-600">Sampah Organik</span>
                                <span class="font-semibold text-slate-800" x-text="(result.breakdown?.organic || 0) + '%'"></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + (result.breakdown?.organic || 0) + '%'"></div>
                            </div>
                        </div>

                        <!-- Plastic Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-600">Sampah Plastik</span>
                                <span class="font-semibold text-slate-800" x-text="(result.breakdown?.plastic || 0) + '%'"></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + (result.breakdown?.plastic || 0) + '%'"></div>
                            </div>
                        </div>

                        <!-- Paper Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-600">Sampah Kertas</span>
                                <span class="font-semibold text-slate-800" x-text="(result.breakdown?.paper || 0) + '%'"></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-amber-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + (result.breakdown?.paper || 0) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Eco Recommendation -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                        <span class="font-semibold text-slate-900 block">Rekomendasi penanganan:</span>
                        <p class="text-slate-700 font-medium" x-text="result.recommendation?.type"></p>
                        <p class="text-slate-600 text-[11px]" x-text="result.recommendation?.action"></p>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Gunakan hasil ini untuk membuat laporan</span>
                <a href="{{ route('reports.create') }}" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                    Lanjut ke form laporan
                </a>
            </div>

                        <!-- Plastic Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-semibold text-blue-700 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Plastic Waste (Sampah Plastik)
                                </span>
                                <span class="font-bold text-slate-800" x-text="(result.breakdown?.plastic || 0) + '%'"></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-500" :style="'width: ' + (result.breakdown?.plastic || 0) + '%'"></div>
                            </div>
                        </div>

                        <!-- Paper Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-semibold text-amber-700 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Paper Waste (Sampah Kertas)
                                </span>
                                <span class="font-bold text-slate-800" x-text="(result.breakdown?.paper || 0) + '%'"></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500" :style="'width: ' + (result.breakdown?.paper || 0) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Eco Recommendation -->
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs space-y-1">
                        <span class="font-bold text-emerald-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-recycle text-emerald-600"></i> Rekomendasi Pengolahan & Daur Ulang:
                        </span>
                        <p class="text-emerald-800 font-medium text-[11px]" x-text="result.recommendation?.type"></p>
                        <p class="text-emerald-700 text-[11px]" x-text="result.recommendation?.action"></p>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Gunakan hasil ini untuk membuat laporan</span>
                <a href="{{ route('reports.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                    Buat Laporan dengan Foto Ini
                </a>
            </div>
        </div>

    </div>

    <!-- Machine Learning Performance & Metrics Panel -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        @if($evaluation)
            @php
                $classLabels = [
                    'organic' => 'Sampah Organik',
                    'plastic' => 'Sampah Plastik',
                    'paper' => 'Sampah Kertas',
                ];
                $report = $evaluation['classification_report'];
                $macro = $report['macro avg'];
                $splitSizes = $evaluation['split_sizes'];
            @endphp

            <div>
                <h3 class="text-sm font-bold text-slate-900">Hasil Evaluasi Model (Test Set)</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Split stratified: {{ $splitSizes['train'] }} train, {{ $splitSizes['validation'] }} validation, dan {{ $splitSizes['test'] }} test. Random seed {{ $evaluation['random_seed'] }}.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border border-slate-200 rounded-lg">
                    <thead class="bg-slate-50 text-slate-600 font-medium border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Kategori</th>
                            <th class="px-4 py-2.5">Precision</th>
                            <th class="px-4 py-2.5">Recall</th>
                            <th class="px-4 py-2.5">F1-Score</th>
                            <th class="px-4 py-2.5">Jumlah Sampel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($evaluation['classes'] as $class)
                            @php($metrics = $report[$class])
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-slate-900">{{ $classLabels[$class] ?? $class }}</td>
                                <td class="px-4 py-2.5">{{ number_format($metrics['precision'] * 100, 2) }}%</td>
                                <td class="px-4 py-2.5">{{ number_format($metrics['recall'] * 100, 2) }}%</td>
                                <td class="px-4 py-2.5">{{ number_format($metrics['f1-score'] * 100, 2) }}%</td>
                                <td class="px-4 py-2.5">{{ (int) $metrics['support'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50/70 border-t border-slate-200 font-semibold text-slate-900">
                        <tr>
                            <td class="px-4 py-2.5">Macro average / akurasi</td>
                            <td class="px-4 py-2.5">{{ number_format($macro['precision'] * 100, 2) }}%</td>
                            <td class="px-4 py-2.5">{{ number_format($macro['recall'] * 100, 2) }}%</td>
                            <td class="px-4 py-2.5">{{ number_format($macro['f1-score'] * 100, 2) }}%</td>
                            <td class="px-4 py-2.5">{{ $splitSizes['test'] }} · {{ number_format($evaluation['test_accuracy'] * 100, 2) }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="space-y-2">
                <h4 class="text-xs font-bold text-slate-900">Confusion matrix</h4>
                <p class="text-[11px] text-slate-500">Baris menunjukkan label sebenarnya; kolom menunjukkan prediksi model.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-center text-xs border border-slate-200 rounded-lg">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Aktual \ Prediksi</th>
                                @foreach($evaluation['classes'] as $class)
                                    <th class="px-3 py-2">{{ $classLabels[$class] ?? $class }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($evaluation['classes'] as $rowIndex => $class)
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">{{ $classLabels[$class] ?? $class }}</th>
                                    @foreach($evaluation['confusion_matrix'][$rowIndex] as $count)
                                        <td class="px-3 py-2">{{ $count }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                    <span class="font-bold text-slate-900 block">
                        Target 80% {{ $evaluation['target_accuracy_met'] ? 'terpenuhi' : 'belum terpenuhi' }}
                    </span>
                    <span class="text-slate-500 text-[11px]">
                        Akurasi test {{ number_format($evaluation['test_accuracy'] * 100, 2) }}% dari {{ $splitSizes['test'] }} foto. Dataset kecil, hasil bukan jaminan performa pada kondisi nyata.
                    </span>
                </div>
                <a href="{{ route('docs.api') }}" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition text-xs">
                    Dokumentasi API
                </a>
            </div>
        @else
            <div>
                <h3 class="text-sm font-bold text-slate-900">Hasil Evaluasi Model</h3>
                <p class="text-xs text-slate-500 mt-1">Belum ada hasil evaluasi. Jalankan training dari folder <code>machine-learning</code> untuk membuat report dan confusion matrix.</p>
            </div>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function aiLabComponent() {
        return {
            imagePreview: null,
            loading: false,
            error: '',
            uncertain: false,
            message: '',
            result: {
                category: '',
                confidence: null,
                label: '',
                breakdown: null,
                recommendation: null,
                source: ''
            },

            onFileSelected(event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);

                this.classifyImage(file);
            },

            async classifyImage(file) {
                this.loading = true;
                this.error = '';
                this.uncertain = false;
                this.message = '';
                this.result = {
                    category: '',
                    confidence: null,
                    label: '',
                    breakdown: null,
                    recommendation: null,
                    source: ''
                };
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
                    if (!res.ok || !json.success || !json.data) {
                        this.error = json.message || 'Layanan AI tidak dapat memproses gambar.';
                        return;
                    }

                    if (json.data.status === 'uncertain') {
                        this.uncertain = true;
                        this.message = json.message || 'Coba gunakan foto yang lebih jelas.';
                        return;
                    }

                    this.result = json.data;
                } catch (err) {
                    console.error('Classification error:', err);
                    this.error = 'Tidak dapat terhubung ke layanan AI. Pastikan layanan AI berjalan, lalu coba lagi.';
                } finally {
                    this.loading = false;
                }
            },

            loadSample(category) {
                // Generate a visual sample canvas image to test directly
                const canvas = document.createElement('canvas');
                canvas.width = 256;
                canvas.height = 256;
                const ctx = canvas.getContext('2d');

                if (category === 'organic') {
                    ctx.fillStyle = '#2d6a4f';
                    ctx.fillRect(0, 0, 256, 256);
                    ctx.fillStyle = '#52b788';
                    ctx.beginPath();
                    ctx.arc(128, 128, 80, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.fillStyle = '#d8f3dc';
                    ctx.font = 'bold 20px sans-serif';
                    ctx.fillText('Organic Waste', 60, 135);
                } else if (category === 'plastic') {
                    ctx.fillStyle = '#1d3557';
                    ctx.fillRect(0, 0, 256, 256);
                    ctx.fillStyle = '#457b9d';
                    ctx.beginPath();
                    ctx.roundRect(78, 48, 100, 160, 20);
                    ctx.fill();
                    ctx.fillStyle = '#a8dadc';
                    ctx.font = 'bold 20px sans-serif';
                    ctx.fillText('Plastic Bottle', 65, 135);
                } else {
                    ctx.fillStyle = '#d4a373';
                    ctx.fillRect(0, 0, 256, 256);
                    ctx.fillStyle = '#faedcd';
                    ctx.beginPath();
                    ctx.rect(48, 48, 160, 160);
                    ctx.fill();
                    ctx.fillStyle = '#bc6c25';
                    ctx.font = 'bold 20px sans-serif';
                    ctx.fillText('Cardboard Paper', 50, 135);
                }

                canvas.toBlob((blob) => {
                    const file = new File([blob], `${category}_sample.jpg`, { type: 'image/jpeg' });
                    this.imagePreview = canvas.toDataURL('image/jpeg');
                    this.classifyImage(file);
                }, 'image/jpeg');
            }
        };
    }
</script>
@endpush
@endsection
