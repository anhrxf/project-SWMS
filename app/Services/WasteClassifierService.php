<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WasteClassifierService
{
    /**
     * Classify an uploaded image using the trained Python model.
     *
     * @return array{source: string, status: string, message: string, category: ?string, confidence: float, label: ?string, breakdown: array<string, float>, recommendation: ?array}
     */
    public function classify(string $imagePath): array
    {
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            throw new WasteClassificationException('File gambar tidak dapat dibaca.');
        }

        $image = file_get_contents($imagePath);
        if ($image === false) {
            throw new WasteClassificationException('File gambar tidak dapat dibaca.');
        }

        $url = config('services.ml.url');
        if (!is_string($url) || $url === '') {
            throw new WasteClassificationException('URL layanan klasifikasi AI belum dikonfigurasi.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->attach('file', $image, basename($imagePath))
                ->post($url);
        } catch (ConnectionException $exception) {
            Log::error('Waste classification service is unreachable.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            throw new WasteClassificationException(
                'Layanan klasifikasi AI tidak tersedia. Coba lagi setelah layanan aktif.',
                previous: $exception,
            );
        }

        if (!$response->successful()) {
            Log::error('Waste classification service returned an error.', [
                'status' => $response->status(),
            ]);

            $message = $response->status() === 422
                ? 'Gambar tidak dapat diproses oleh layanan AI.'
                : 'Layanan klasifikasi AI tidak tersedia. Coba lagi setelah layanan aktif.';

            throw new WasteClassificationException(
                $message,
                $response->status() === 422 ? 422 : 503,
            );
        }

        $data = $response->json();
        $status = is_array($data) ? ($data['status'] ?? null) : null;
        $category = is_array($data) ? ($data['category'] ?? null) : null;
        $confidence = is_array($data) ? ($data['confidence'] ?? null) : null;
        $breakdown = is_array($data) ? ($data['breakdown'] ?? null) : null;
        $categories = ['organic', 'plastic', 'paper'];

        if (
            !in_array($status, ['classified', 'uncertain'], true)
            || !is_numeric($confidence)
            || (float) $confidence < 0
            || (float) $confidence > 100
            || !is_array($breakdown)
            || !is_string($data['message'] ?? null)
        ) {
            Log::error('Waste classification service returned an invalid prediction.');

            throw new WasteClassificationException('Layanan klasifikasi AI mengembalikan hasil yang tidak valid.');
        }

        if (
            ($status === 'classified' && (!is_string($category) || !in_array($category, $categories, true)))
            || ($status === 'uncertain' && $category !== null)
        ) {
            Log::error('Waste classification service returned an invalid classification status.');

            throw new WasteClassificationException('Layanan klasifikasi AI mengembalikan hasil yang tidak valid.');
        }

        $normalizedBreakdown = [];
        foreach ($categories as $name) {
            $value = $breakdown[$name] ?? null;
            if (!is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                Log::error('Waste classification service returned an invalid probability breakdown.');

                throw new WasteClassificationException('Layanan klasifikasi AI mengembalikan hasil yang tidak valid.');
            }
            $normalizedBreakdown[$name] = (float) $value;
        }

        return [
            'source' => 'pytorch_fastapi',
            'status' => $status,
            'message' => $data['message'],
            'category' => $category,
            'confidence' => (float) $confidence,
            'label' => $category ? $this->getCategoryLabel($category) : null,
            'breakdown' => $normalizedBreakdown,
            'recommendation' => $category ? $this->getRecommendation($category) : null,
        ];
    }

    public function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'organic' => 'Sampah Organik (Organic Waste)',
            'plastic' => 'Sampah Plastik (Plastic Waste)',
            'paper' => 'Sampah Kertas & Karton (Paper Waste)',
            default => 'Sampah Umum / Lainnya',
        };
    }

    public function getRecommendation(string $category): array
    {
        return match ($category) {
            'organic' => [
                'type' => 'Kompos / Biopori / Pakan Maggot',
                'action' => 'Pisahkan dari sampah anorganik. Sampah ini dapat diolah menjadi pupuk kompos atau dimasukkan ke dalam lubang biopori.',
                'color_code' => '#10b981',
                'bin' => 'Tempat Sampah Hijau (Organik)',
            ],
            'plastic' => [
                'type' => 'Daur Ulang Plastik / Bank Sampah',
                'action' => 'Bilas hingga bersih dari sisa cairan atau minyak, remukkan botol untuk menghemat ruang, dan kumpulkan ke Bank Sampah terdekat.',
                'color_code' => '#3b82f6',
                'bin' => 'Tempat Sampah Kuning (Anorganik / Plastik)',
            ],
            'paper' => [
                'type' => 'Daur Ulang Kertas / Pabrik Pulp',
                'action' => 'Pastikan kering dan tidak terkena minyak atau air. Lipat kardus/kertas rapi untuk didistribusikan ke pengepul atau bank sampah.',
                'color_code' => '#f59e0b',
                'bin' => 'Tempat Sampah Biru (Kertas)',
            ],
            default => [
                'type' => 'Penanganan Khusus / Residu',
                'action' => 'Kemas dengan aman dan laporkan kepada petugas kebersihan terdekat.',
                'color_code' => '#64748b',
                'bin' => 'Tempat Sampah Abu-abu (Residu)',
            ],
        };
    }
}
