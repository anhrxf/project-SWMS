<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // 1. Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'swms.admin@gmail.com'],
            [
                'name' => 'Administrator SWMS',
                'password' => Hash::make('admin.swms2026'),
                'role' => 'admin',
                'phone' => '081234567890',
                'address' => 'Kantor Dinas Lingkungan Hidup, Lantai 2',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Default Regular Users
        $user1 = User::firstOrCreate(
            ['email' => 'user@swms.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '081298765432',
                'address' => 'Jl. Kebon Jeruk Raya No. 12, Jakarta Barat',
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'siti@swms.com'],
            [
                'name' => 'Siti Rahmawati',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '085612348899',
                'address' => 'Jl. Dago Asri No. 45, Bandung',
                'email_verified_at' => now(),
            ]
        );

        foreach ([$admin, $user1, $user2] as $demoUser) {
            if (! $demoUser->hasVerifiedEmail()) {
                $demoUser->markEmailAsVerified();
            }
        }

        // 3. Generate Realistic Sample Images via GD
        $reportsDir = storage_path('app/public/reports');
        if (! file_exists($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $imageFiles = [
            'organic_leaves.jpg' => ['color' => [46, 125, 50], 'text' => 'Sampah Daun & Ranting'],
            'organic_food.jpg' => ['color' => [104, 159, 56], 'text' => 'Sisa Makanan Sayuran'],
            'plastic_bottles.jpg' => ['color' => [25, 118, 210], 'text' => 'Tumpukan Botol Plastik'],
            'plastic_bags.jpg' => ['color' => [2, 136, 209], 'text' => 'Kantong Kresek Plastik'],
            'paper_cardboard.jpg' => ['color' => [216, 150, 60], 'text' => 'Timbunan Kardus Bekas'],
            'paper_newspaper.jpg' => ['color' => [180, 140, 90], 'text' => 'Koran & Kertas Kantor'],
        ];

        foreach ($imageFiles as $filename => $meta) {
            $path = $reportsDir.'/'.$filename;
            if (! file_exists($path) && extension_loaded('gd')) {
                $im = imagecreatetruecolor(600, 400);
                $bg = imagecolorallocate($im, $meta['color'][0], $meta['color'][1], $meta['color'][2]);
                imagefill($im, 0, 0, $bg);

                // Add texture pattern
                $patternColor = imagecolorallocate($im, 255, 255, 255);
                for ($i = 0; $i < 400; $i += 40) {
                    imageline($im, 0, $i, 600, $i + 20, $patternColor);
                }

                // Add text box
                $boxBg = imagecolorallocate($im, 15, 23, 42);
                imagefilledrectangle($im, 40, 150, 560, 250, $boxBg);

                $textColor = imagecolorallocate($im, 255, 255, 255);
                imagestring($im, 5, 60, 180, 'SWMS AI Detection Sample', $textColor);
                imagestring($im, 4, 60, 210, $meta['text'], $textColor);

                imagejpeg($im, $path, 90);
                imagedestroy($im);
            }
        }

        // 4. Seed Realistic Reports with Coordinates and Status Logs
        $sampleReports = [
            [
                'user_id' => $user1->id,
                'title' => 'Timbunan Botol Plastik dan Gelas Mineral di Dekat Halte',
                'description' => 'Banyak botol plastik sekali pakai dan gelas air mineral berserakan di sekitar halte busway, mengganggu pejalan kaki dan rawan menyumbat saluran air.',
                'waste_category' => 'plastic',
                'ai_detected_category' => 'plastic',
                'ai_confidence' => 95.4,
                'image_path' => 'reports/plastic_bottles.jpg',
                'latitude' => -6.175392,
                'longitude' => 106.827153,
                'location_address' => 'Jl. Medan Merdeka Barat, Halte Monas, Gambir',
                'status' => 'completed',
                'admin_notes' => 'Armada 01 Dinas Lingkungan Hidup telah mengangkut sampah plastik dan menyalurkannya ke TPS3R Gambir.',
                'created_at' => Carbon::now()->subDays(5),
                'resolved_at' => Carbon::now()->subDays(4),
            ],
            [
                'user_id' => $user1->id,
                'title' => 'Sisa Makanan dan Sayuran Membusuk di Belakang Pasar',
                'description' => 'Tumpukan sisa sayuran, buah busuk, dan ampas makanan pedagang menumpuk hingga mengeluarkan aroma tidak sedap.',
                'waste_category' => 'organic',
                'ai_detected_category' => 'organic',
                'ai_confidence' => 93.8,
                'image_path' => 'reports/organic_food.jpg',
                'latitude' => -6.185621,
                'longitude' => 106.842341,
                'location_address' => 'Kawasan Pasar Senen Blok III, Jakarta Pusat',
                'status' => 'in_progress',
                'admin_notes' => 'Tim petugas kebersihan sedang dalam perjalanan dengan truk compactor.',
                'created_at' => Carbon::now()->subDays(3),
                'resolved_at' => null,
            ],
            [
                'user_id' => $user2->id,
                'title' => 'Kardus dan Kemasan Paket Menumpuk di Depan Ruko',
                'description' => 'Bekas kardus ekspedisi dan karton tebal basah terkena hujan di trotoar depan pertokoan.',
                'waste_category' => 'paper',
                'ai_detected_category' => 'paper',
                'ai_confidence' => 91.2,
                'image_path' => 'reports/paper_cardboard.jpg',
                'latitude' => -6.917464,
                'longitude' => 107.619123,
                'location_address' => 'Jl. Asia Afrika No. 78, Kota Bandung',
                'status' => 'pending',
                'admin_notes' => null,
                'created_at' => Carbon::now()->subDays(1),
                'resolved_at' => null,
            ],
            [
                'user_id' => $user2->id,
                'title' => 'Ranting dan Daun Kering Akibat Pemangkasan Pohon',
                'description' => 'Sisa pemotongan dahan dan daun berserakan memenuhi badan jalan setelah hujan angin.',
                'waste_category' => 'organic',
                'ai_detected_category' => 'organic',
                'ai_confidence' => 96.1,
                'image_path' => 'reports/organic_leaves.jpg',
                'latitude' => -6.890123,
                'longitude' => 107.610456,
                'location_address' => 'Jl. Dipati Ukur, Coblong, Kota Bandung',
                'status' => 'completed',
                'admin_notes' => 'Pembersihan selesai dan daun telah dibawa ke pusat pembuatan kompos Babakan Siliwangi.',
                'created_at' => Carbon::now()->subDays(6),
                'resolved_at' => Carbon::now()->subDays(5),
            ],
            [
                'user_id' => $user1->id,
                'title' => 'Kantong Kresek Plastik Menumpuk di Saluran Drainase',
                'description' => 'Kantong kresek bekas dan styrofoam menyumbat gorong-gorong, berpotensi banjir jika hujan deras.',
                'waste_category' => 'plastic',
                'ai_detected_category' => 'plastic',
                'ai_confidence' => 88.7,
                'image_path' => 'reports/plastic_bags.jpg',
                'latitude' => -6.225678,
                'longitude' => 106.801234,
                'location_address' => 'Jl. Gatot Subroto, Kebayoran Baru, Jakarta Selatan',
                'status' => 'in_progress',
                'admin_notes' => 'Petugas PPSU sedang mengeruk sumbatan saluran air.',
                'created_at' => Carbon::now()->subHours(18),
                'resolved_at' => null,
            ],
            [
                'user_id' => $user2->id,
                'title' => 'Timbunan Koran dan Kertas Arsip Terbengkalai',
                'description' => 'Kertas dokumen bekas dan karton arsip ditinggalkan di pinggir jalan umum.',
                'waste_category' => 'paper',
                'ai_detected_category' => 'paper',
                'ai_confidence' => 92.5,
                'image_path' => 'reports/paper_newspaper.jpg',
                'latitude' => -6.903891,
                'longitude' => 107.618612,
                'location_address' => 'Jl. R.E. Martadinata (Riau), Bandung',
                'status' => 'pending',
                'admin_notes' => null,
                'created_at' => Carbon::now()->subHours(6),
                'resolved_at' => null,
            ],
            [
                'user_id' => $user1->id,
                'title' => 'Laporan Palsu / Sampah Tidak Ditemukan',
                'description' => 'Foto tidak jelas dan saat disurvei lokasi bersih dari sampah.',
                'waste_category' => 'other',
                'ai_detected_category' => 'other',
                'ai_confidence' => 81.0,
                'image_path' => 'reports/organic_food.jpg',
                'latitude' => -6.195000,
                'longitude' => 106.830000,
                'location_address' => 'Jl. Thamrin No. 1, Jakarta Pusat',
                'status' => 'rejected',
                'admin_notes' => 'Laporan ditolak karena saat petugas memeriksa lokasi, tidak ada timbunan sampah.',
                'created_at' => Carbon::now()->subDays(2),
                'resolved_at' => null,
            ],
        ];

        foreach ($sampleReports as $repData) {
            $report = Report::firstOrCreate(
                [
                    'user_id' => $repData['user_id'],
                    'title' => $repData['title'],
                ],
                $repData
            );

            if (! $report->wasRecentlyCreated) {
                continue;
            }

            // Create initial status log
            ReportStatusLog::create([
                'report_id' => $report->id,
                'user_id' => $report->user_id,
                'previous_status' => null,
                'new_status' => 'pending',
                'notes' => 'Laporan awal dibuat oleh pelapor.',
                'created_at' => $report->created_at,
            ]);

            // If progressed or completed, create subsequent logs
            if ($report->status === 'in_progress' || $report->status === 'completed') {
                ReportStatusLog::create([
                    'report_id' => $report->id,
                    'user_id' => $admin->id,
                    'previous_status' => 'pending',
                    'new_status' => 'in_progress',
                    'notes' => 'Status diubah ke In Progress. Petugas armada kebersihan ditugaskan ke lokasi.',
                    'created_at' => $report->created_at->addHours(2),
                ]);
            }

            if ($report->status === 'completed') {
                ReportStatusLog::create([
                    'report_id' => $report->id,
                    'user_id' => $admin->id,
                    'previous_status' => 'in_progress',
                    'new_status' => 'completed',
                    'notes' => $report->admin_notes ?: 'Pengangkutan sampah telah selesai dilaksanakan.',
                    'created_at' => $report->resolved_at ?? $report->created_at->addHours(6),
                ]);
            }

            if ($report->status === 'rejected') {
                ReportStatusLog::create([
                    'report_id' => $report->id,
                    'user_id' => $admin->id,
                    'previous_status' => 'pending',
                    'new_status' => 'rejected',
                    'notes' => $report->admin_notes ?: 'Laporan ditolak.',
                    'created_at' => $report->created_at->addHours(1),
                ]);
            }
        }
    }
}
