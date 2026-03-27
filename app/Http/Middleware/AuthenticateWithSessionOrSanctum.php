<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateWithSessionOrSanctum
{
    public function handle(Request $request, Closure $next)
    {
        // Try Sanctum first (for token-based auth)
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Fall back to session (for session-based auth from dashboard)
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // If not authenticated, return 401 JSON response
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
            'errors' => null,
        ], 401);
    }
}
