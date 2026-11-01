<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Endpoint ini hanya dapat diakses oleh Administrator.',
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Halaman tersebut khusus untuk Administrator.');
        }

        return $next($request);
    }
}
