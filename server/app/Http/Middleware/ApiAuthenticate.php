<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'status' => false,
                'message' => 'Please login again'
            ], 401);
        }

        return $next($request);
    }
}
