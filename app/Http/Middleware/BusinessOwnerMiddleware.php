<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BusinessOwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $role = $request->user()?->role;

        if (!$role || !in_array($role, ['business', 'employer', 'admin'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Business owner access required.',
                ], 403);
            }

            return redirect('/dashboard')->with('error', 'Unauthorized. Business owner access required.');
        }

        return $next($request);
    }
}
