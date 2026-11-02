<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Services\WasteClassificationException;
use App\Services\WasteClassifierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected WasteClassifierService $classifier
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $query = Report::with(['user', 'statusLogs'])->latest();

        // Non-admin only sees own reports
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Search keyword
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter waste category
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('waste_category', $request->input('category'));
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $reports = $query->paginate(9)->withQueryString();

        // Status counts for quick filters
        $baseCountQuery = Report::query();
        if (!$isAdmin) {
            $baseCountQuery->where('user_id', $user->id);
        }
        $statusCounts = [
            'all' => (clone $baseCountQuery)->count(),
            'pending' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseCountQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'rejected' => (clone $baseCountQuery)->where('status', 'rejected')->count(),
        ];

        return view('reports.index', [
            'reports' => $reports,
            'statusCounts' => $statusCounts,
            'isAdmin' => $isAdmin,
            'filters' => $request->only(['search', 'status', 'category', 'date_from', 'date_to']),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::user()->isAdmin()) {
            return redirect()->route('reports.index')
                ->with('error', 'Administrator hanya bertugas memantau dan memverifikasi laporan. Pembuatan laporan hanya dilakukan oleh masyarakat/user.');
        }

        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::user()->isAdmin()) {
            return redirect()->route('reports.index')
                ->with('error', 'Administrator tidak diizinkan membuat laporan sampah.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'waste_category' => ['required', 'string', 'in:organic,plastic,paper,other'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Judul laporan wajib diisi.',
            'description.required' => 'Deskripsi sampah wajib diisi.',
            'waste_category.required' => 'Kategori sampah wajib dipilih.',
            'image.required' => 'Foto sampah wajib diunggah.',
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 10MB.',
        ]);

        $imagePath = $request->file('image')->store('reports', 'public');
        $fullPath = Storage::disk('public')->path($imagePath);

        try {
            $analysis = $this->classifier->classify($fullPath);
        } catch (WasteClassificationException $exception) {
            Storage::disk('public')->delete($imagePath);

            return back()
                ->withInput()
                ->withErrors(['image' => $exception->getMessage()]);
        }

        $report = Report::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'waste_category' => $validated['waste_category'],
            'ai_detected_category' => $analysis['category'],
            'ai_confidence' => $analysis['confidence'],
            'image_path' => $imagePath,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'location_address' => $validated['location_address'] ?? null,
            'status' => 'pending',
        ]);

        // Initial status log
        ReportStatusLog::create([
            'report_id' => $report->id,
            'user_id' => Auth::id(),
            'previous_status' => null,
            'new_status' => 'pending',
            'notes' => 'Laporan berhasil dibuat oleh ' . Auth::user()->name . '.',
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan sampah berhasil dikirim! Tim operasional akan segera menindaklanjuti.')
            ->with('clear_report_draft', true);
    }

    public function show(Report $report): View
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) {
            abort(403, 'Anda tidak berhak melihat laporan ini.');
        }

        $report->load(['user', 'statusLogs.user']);
        $recommendation = $this->classifier->getRecommendation($report->waste_category);

        return view('reports.show', [
            'report' => $report,
            'recommendation' => $recommendation,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function edit(Report $report): View
    {
        $user = Auth::user();
        if ($user->isAdmin() || $report->user_id !== $user->id || $report->status !== 'pending') {
            abort(403, 'Akses ditolak. Laporan hanya dapat diedit oleh pelapor aslinya selama status masih Pending.');
        }

        return view('reports.edit', [
            'report' => $report,
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isAdmin() || $report->user_id !== $user->id || $report->status !== 'pending') {
            abort(403, 'Akses ditolak. Hanya pelapor yang dapat memperbarui data laporan saat berstatus Pending.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'waste_category' => ['required', 'string', 'in:organic,plastic,paper,other'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'waste_category' => $validated['waste_category'],
            'latitude' => $validated['latitude'] ?? $report->latitude,
            'longitude' => $validated['longitude'] ?? $report->longitude,
            'location_address' => $validated['location_address'] ?? $report->location_address,
        ];

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reports', 'public');
            $fullPath = Storage::disk('public')->path($imagePath);
            try {
                $analysis = $this->classifier->classify($fullPath);
            } catch (WasteClassificationException $exception) {
                Storage::disk('public')->delete($imagePath);

                return back()
                    ->withInput()
                    ->withErrors(['image' => $exception->getMessage()]);
            }

            $updateData['image_path'] = $imagePath;
            $updateData['ai_detected_category'] = $analysis['category'];
            $updateData['ai_confidence'] = $analysis['confidence'];
        }

        $previousImagePath = $report->image_path;
        $report->update($updateData);

        if (
            isset($imagePath)
            && $previousImagePath
            && Storage::disk('public')->exists($previousImagePath)
        ) {
            Storage::disk('public')->delete($previousImagePath);
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(Report $report): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isAdmin() || $report->user_id !== $user->id || $report->status !== 'pending') {
            abort(403, 'Laporan hanya dapat dibatalkan atau dihapus oleh pelapor aslinya selama status masih Pending.');
        }

        if ($report->image_path && Storage::disk('public')->exists($report->image_path)) {
            Storage::disk('public')->delete($report->image_path);
        }

        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Laporan berhasil dihapus.');
    }

    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat mengubah status laporan.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => 'Pilih status baru untuk laporan.',
            'status.in' => 'Status tidak valid.',
        ]);

        $oldStatus = $report->status;
        $newStatus = $validated['status'];
        $notes = $validated['admin_notes'] ?? null;

        $report->status = $newStatus;
        $report->admin_notes = $notes;
        if ($newStatus === 'completed') {
            $report->resolved_at = now();
        } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
            $report->resolved_at = null;
        }
        $report->save();

        // Record status log
        ReportStatusLog::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes ?: 'Status diubah menjadi ' . $report->status_label . ' oleh Administrator.',
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', "Status laporan berhasil diperbarui menjadi {$report->status_label}.");
    }
}
