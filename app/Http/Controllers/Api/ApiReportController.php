<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Services\WasteClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiReportController extends Controller
{
    public function __construct(
        protected WasteClassifierService $classifier
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Report::with(['user:id,name,email', 'statusLogs'])->latest();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('waste_category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_address', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Administrator hanya bertugas memantau laporan dan tidak diizinkan membuat laporan sampah.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'waste_category' => ['required', 'string', 'in:organic,plastic,paper,other'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:500'],
            'ai_detected_category' => ['nullable', 'string'],
            'ai_confidence' => ['nullable', 'numeric'],
        ]);

        $imagePath = $request->file('image')->store('reports', 'public');
        $fullPath = storage_path('app/public/' . $imagePath);

        $aiDetected = $validated['ai_detected_category'] ?? null;
        $aiConfidence = $validated['ai_confidence'] ?? null;

        if (!$aiDetected || !$aiConfidence) {
            $analysis = $this->classifier->classify($fullPath);
            $aiDetected = $analysis['category'];
            $aiConfidence = $analysis['confidence'];
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'waste_category' => $validated['waste_category'],
            'ai_detected_category' => $aiDetected,
            'ai_confidence' => $aiConfidence,
            'image_path' => $imagePath,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'location_address' => $validated['location_address'] ?? null,
            'status' => 'pending',
        ]);

        ReportStatusLog::create([
            'report_id' => $report->id,
            'user_id' => $request->user()->id,
            'previous_status' => null,
            'new_status' => 'pending',
            'notes' => 'Laporan dibuat via Mobile API.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibuat.',
            'data' => $report->load(['user:id,name,email', 'statusLogs']),
        ], 201);
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $report->load(['user:id,name,email', 'statusLogs.user:id,name,email']);
        $recommendation = $this->classifier->getRecommendation($report->waste_category);

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'image_url' => $report->image_url,
                'recommendation' => $recommendation,
            ],
        ]);
    }

    public function updateStatus(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang dapat mengubah status laporan.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $report->status;
        $report->status = $validated['status'];
        $report->admin_notes = $validated['admin_notes'] ?? null;
        if ($validated['status'] === 'completed') {
            $report->resolved_at = now();
        } elseif ($oldStatus === 'completed' && $validated['status'] !== 'completed') {
            $report->resolved_at = null;
        }
        $report->save();

        ReportStatusLog::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'previous_status' => $oldStatus,
            'new_status' => $validated['status'],
            'notes' => $validated['admin_notes'] ?? 'Status diubah via API oleh Admin.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diperbarui.',
            'data' => $report->fresh(['statusLogs']),
        ]);
    }
}
