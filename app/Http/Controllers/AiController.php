<?php

namespace App\Http\Controllers;

use App\Services\WasteClassificationException;
use App\Services\WasteClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AiController extends Controller
{
    public function __construct(
        protected WasteClassifierService $classifier
    ) {}

    /**
     * View for interactive AI Waste Classification Lab/Sandbox.
     */
    public function index(): View
    {
        $evaluationPath = base_path('machine-learning/artifacts/evaluation.json');
        $evaluation = null;

        if (is_file($evaluationPath)) {
            $contents = file_get_contents($evaluationPath);
            if ($contents === false) {
                throw new RuntimeException('Hasil evaluasi model tidak dapat dibaca.');
            }

            $evaluation = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (
                !is_array($evaluation)
                || !is_numeric($evaluation['test_accuracy'] ?? null)
                || !is_array($evaluation['classes'] ?? null)
                || !is_array($evaluation['unique_images_per_class'] ?? null)
                || !is_array($evaluation['split_sizes'] ?? null)
                || !is_array($evaluation['classification_report'] ?? null)
                || !is_array($evaluation['confusion_matrix'] ?? null)
                || !is_bool($evaluation['target_accuracy_met'] ?? null)
            ) {
                throw new RuntimeException('Format hasil evaluasi model tidak valid.');
            }
        }

        return view('ai.index', ['evaluation' => $evaluation]);
    }

    /**
     * AJAX endpoint to classify an uploaded waste image.
     */
    public function classify(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ], [
            'image.required' => 'File gambar sampah wajib disertakan.',
            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 10MB.',
        ]);

        $file = $request->file('image');
        $tempPath = $file->getRealPath();

        try {
            $result = $this->classifier->classify($tempPath);
        } catch (WasteClassificationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result,
        ]);
    }
}
