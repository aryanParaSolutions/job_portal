<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        // For API routes, never redirect
        if ($request->is('api/*')) {
            abort(response()->json([
                'status' => false,
                'message' => 'Please login again.'
            ], 401));
        }

        // For web routes, redirect to login page
        return route('login');
    }
}
