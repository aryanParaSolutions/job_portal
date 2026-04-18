<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $roleSlug = $user->role?->slug;

        if (! $roleSlug || ! in_array($roleSlug, $roles, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}