<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\TokenService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = env('AUTH_ACCESS_COOKIE_NAME', 'access_token');
        $jwt = $request->cookie($cookieName);

        if (! $jwt) {
            return $this->unauthorized('Missing access token.', 'ACCESS_TOKEN_MISSING');
        }

        try {
            $payload = $this->tokenService->decodeAccessToken($jwt);
        } catch (ExpiredException $e) {
            return $this->unauthorized('Access token expired.', 'ACCESS_TOKEN_EXPIRED');
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid access token.', 'ACCESS_TOKEN_INVALID');
        }

        if (($payload->typ ?? null) !== 'access') {
            return $this->unauthorized('Invalid access token type.', 'ACCESS_TOKEN_INVALID_TYPE');
        }

        $userId = (int) ($payload->sub ?? 0);

        if (! $userId) {
            return $this->unauthorized('Invalid token subject.', 'ACCESS_TOKEN_INVALID_SUBJECT');
        }

        $user = User::with('role')
            ->where('id', $userId)
            ->where('is_deleted', 0)
            ->first();

        if (! $user) {
            return $this->unauthorized('User not found.', 'AUTH_USER_NOT_FOUND');
        }

        if ($user->status !== 'active') {
            return $this->unauthorized('User account is not active.', 'AUTH_USER_INACTIVE');
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(string $message, string $code): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'code' => $code,
        ], 401);
    }
}