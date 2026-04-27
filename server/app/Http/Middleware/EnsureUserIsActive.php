<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((bool) $user->is_deleted === true) {
            return response()->json([
                'status' => false,
                'message' => 'User account is deleted.',
                'code' => 'AUTH_USER_DELETED',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'User account is not active.',
                'code' => 'AUTH_USER_INACTIVE',
            ], 401);
        }

        return $next($request);
    }
}