<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Smart Waste Management System (SWMS)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/register', [ApiAuthController::class, 'register']);
    Route::post('/auth/login', [ApiAuthController::class, 'login']);

    // Public AI Image Classifier endpoint
    Route::post('/ai/classify', [AiController::class, 'classify']);

    // Authenticated API Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
        Route::get('/auth/profile', [ApiAuthController::class, 'profile']);
        Route::post('/auth/email/verification-notification', [ApiAuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');

        Route::middleware('email.verified')->group(function () {
            // Reports API
            Route::get('/reports', [ApiReportController::class, 'index']);
            Route::post('/reports', [ApiReportController::class, 'store']);
            Route::get('/reports/{report}', [ApiReportController::class, 'show']);

            // Admin status update via API
            Route::middleware('admin')->group(function () {
                Route::post('/reports/{report}/status', [ApiReportController::class, 'updateStatus']);
            });
        });
    });
});
