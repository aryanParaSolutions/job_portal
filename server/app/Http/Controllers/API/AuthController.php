<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthCookieService;
use App\Services\Auth\RefreshTokenService;
use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private TokenService $tokenService,
        private RefreshTokenService $refreshTokenService,
        private AuthCookieService $authCookieService
    ) {}

    public function login(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'portal' => ['nullable', 'in:admin,employer,candidate'],
    ]);

    $user = User::with('role:id,name,slug')
        ->where('email', $validated['email'])
        ->where('is_deleted', 0)
        ->first();

    if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
        LoginLog::create([
            'user_id' => $user?->id,
            'email' => $validated['email'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_status' => 'failed',
            'failure_reason' => 'invalid_credentials',
            'logged_in_at' => now(),
        ]);

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    if ($user->status !== 'active') {
        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_status' => $user->status === 'blocked' ? 'blocked' : 'failed',
            'failure_reason' => 'user_not_active',
            'logged_in_at' => now(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Your account is not active.',
        ], 403);
    }

    if (! $user->role) {
        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_status' => 'failed',
            'failure_reason' => 'role_not_configured',
            'logged_in_at' => now(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'User role is not configured.',
        ], 403);
    }

    if (! empty($validated['portal']) && $user->role->slug !== $validated['portal']) {
        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_status' => 'failed',
            'failure_reason' => 'wrong_portal',
            'logged_in_at' => now(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'This account does not belong to the selected portal.',
        ], 403);
    }

    $accessToken = $this->tokenService->issueAccessToken($user);

    $refresh = $this->refreshTokenService->createForUser(
        user: $user,
        ip: $request->ip(),
        userAgent: $request->userAgent()
    );

    $user->forceFill([
        'last_login_ip' => $request->ip(),
    ])->save();

    LoginLog::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'login_status' => 'success',
        'failure_reason' => null,
        'logged_in_at' => now(),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Login successful.',
        'user' => $this->mapUser($user),
    ])
    ->cookie($this->authCookieService->makeAccessTokenCookie($accessToken))
    ->cookie($this->authCookieService->makeRefreshTokenCookieWithValue($refresh['plain_text']));
}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'user' => $this->mapUser($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshTokenPlainText = $request->cookie(env('AUTH_REFRESH_COOKIE_NAME', 'refresh_token'));

        if (! $refreshTokenPlainText) {
            return response()->json([
                'status' => false,
                'message' => 'Missing refresh token.',
                'code' => 'REFRESH_TOKEN_MISSING',
            ], 401);
        }

        $currentRefreshToken = $this->refreshTokenService->findUsableByPlainText($refreshTokenPlainText);

        if (! $currentRefreshToken || ! $currentRefreshToken->user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid refresh token.',
                'code' => 'REFRESH_TOKEN_INVALID',
            ], 401)->cookie($this->authCookieService->clearAccessTokenCookie())
              ->cookie($this->authCookieService->clearRefreshTokenCookie());
        }

        $user = $currentRefreshToken->user;

        if ($user->status !== 'active' || (bool) $user->is_deleted === true) {
            $this->refreshTokenService->revokeByPlainText($refreshTokenPlainText);

            return response()->json([
                'status' => false,
                'message' => 'User account is not active.',
                'code' => 'AUTH_USER_INACTIVE',
            ], 401)->cookie($this->authCookieService->clearAccessTokenCookie())
              ->cookie($this->authCookieService->clearRefreshTokenCookie());
        }

        $rotated = $this->refreshTokenService->rotate(
            currentToken: $currentRefreshToken,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $accessToken = $this->tokenService->issueAccessToken($user);

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully.',
        ])
        ->cookie($this->authCookieService->makeAccessTokenCookie($accessToken))
        ->cookie($this->authCookieService->makeRefreshTokenCookieWithValue($rotated['plain_text']));
    }

    public function logout(Request $request): JsonResponse
    {
        $refreshTokenPlainText = $request->cookie(env('AUTH_REFRESH_COOKIE_NAME', 'refresh_token'));
        $this->refreshTokenService->revokeByPlainText($refreshTokenPlainText);

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.',
        ])
        ->cookie($this->authCookieService->clearAccessTokenCookie())
        ->cookie($this->authCookieService->clearRefreshTokenCookie());
    }

    private function mapUser(User $user): array
    {
        $user->loadMissing('role:id,name,slug');

        return [
            'id' => $user->id,
            'role_id' => $user->role_id,
            'role_slug' => $user->role?->slug,
            'role_name' => $user->role?->name,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'profile_photo' => $user->profile_photo,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ];
    }
}