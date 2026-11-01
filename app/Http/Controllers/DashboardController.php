<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        // Base query depending on role
        $query = Report::query();
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Statistics counts
        $totalReports = (clone $query)->count();
        $pendingReports = (clone $query)->where('status', 'pending')->count();
        $inProgressReports = (clone $query)->where('status', 'in_progress')->count();
        $completedReports = (clone $query)->where('status', 'completed')->count();
        $rejectedReports = (clone $query)->where('status', 'rejected')->count();

        // Resolution rate
        $resolutionRate = $totalReports > 0
            ? round(($completedReports / $totalReports) * 100, 1)
            : 0;

        // Categories breakdown
        $categoriesCount = [
            'organic' => (clone $query)->where('waste_category', 'organic')->count(),
            'plastic' => (clone $query)->where('waste_category', 'plastic')->count(),
            'paper' => (clone $query)->where('waste_category', 'paper')->count(),
            'other' => (clone $query)->where('waste_category', 'other')->count(),
        ];

        // Last 7 days trend
        $dates = [];
        $countsByDate = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            $dates[] = $date->translatedFormat('d M');

            $c = (clone $query)->whereDate('created_at', $formattedDate)->count();
            $countsByDate[] = $c;
        }

        // Recent reports
        $recentReports = (clone $query)
            ->with(['user', 'statusLogs'])
            ->latest()
            ->take(6)
            ->get();

        // System metrics for Admin
        $totalUsers = 0;
        $totalRegularUsers = 0;
        $avgConfidence = 0;

        if ($isAdmin) {
            $totalUsers = User::count();
            $totalRegularUsers = User::where('role', 'user')->count();
            $avgConfidence = round(Report::whereNotNull('ai_confidence')->avg('ai_confidence') ?? 0, 1);
        }

        return view('dashboard.index', [
            'isAdmin' => $isAdmin,
            'totalReports' => $totalReports,
            'pendingReports' => $pendingReports,
            'inProgressReports' => $inProgressReports,
            'completedReports' => $completedReports,
            'rejectedReports' => $rejectedReports,
            'resolutionRate' => $resolutionRate,
            'categoriesCount' => $categoriesCount,
            'chartDates' => $dates,
            'chartCounts' => $countsByDate,
            'recentReports' => $recentReports,
            'totalUsers' => $totalUsers,
            'totalRegularUsers' => $totalRegularUsers,
            'avgConfidence' => $avgConfidence,
        ]);
    }
}
