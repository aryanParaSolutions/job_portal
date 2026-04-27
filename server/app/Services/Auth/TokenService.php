<?php

namespace App\Services\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class TokenService
{
    public function issueAccessToken(User $user): string
    {
        $now = time();
        $ttlMinutes = (int) env('AUTH_ACCESS_TOKEN_TTL_MINUTES', 15);
        $secret = (string) env('AUTH_ACCESS_TOKEN_SECRET');

        if ($secret === '') {
            throw new \RuntimeException('AUTH_ACCESS_TOKEN_SECRET is not configured.');
        }

        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'typ' => 'access',
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttlMinutes * 60),
            'role' => $user->role?->slug,
            'status' => $user->status,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    public function decodeAccessToken(string $jwt): object
    {
        $secret = (string) env('AUTH_ACCESS_TOKEN_SECRET');

        if ($secret === '') {
            throw new \RuntimeException('AUTH_ACCESS_TOKEN_SECRET is not configured.');
        }

        return JWT::decode($jwt, new Key($secret, 'HS256'));
    }

    public function issueRefreshTokenPlainText(): string
    {
        return Str::random(128);
    }

    public function hashRefreshToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    public function generateFamilyId(): string
    {
        return (string) Str::uuid();
    }
}