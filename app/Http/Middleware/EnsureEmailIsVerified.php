<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as Middleware;

class EnsureEmailIsVerified extends Middleware
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (!config('auth.email_verification_enabled')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
