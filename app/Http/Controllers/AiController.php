<?php

namespace App\Http\Controllers;

use App\Services\WasteClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        return view('ai.index');
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

        $result = $this->classifier->classify($tempPath);

        return response()->json([
            'success' => true,
            'message' => 'Analisis gambar berhasil dilakukan.',
            'data' => $result,
        ]);
    }
}
