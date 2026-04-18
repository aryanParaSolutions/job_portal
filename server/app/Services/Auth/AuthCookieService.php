<?php

namespace App\Services\Auth;

use Symfony\Component\HttpFoundation\Cookie;

class AuthCookieService
{
    public function makeAccessTokenCookie(string $token): Cookie
    {
        $minutes = (int) env('AUTH_ACCESS_TOKEN_TTL_MINUTES', 15);

        return cookie(
            name: env('AUTH_ACCESS_COOKIE_NAME', 'access_token'),
            value: $token,
            minutes: $minutes,
            path: '/',
            domain: $this->domain(),
            secure: $this->secure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->sameSite(),
        );
    }

    public function makeRefreshTokenCookieWithValue(string $token): Cookie
    {
        $minutes = ((int) env('AUTH_REFRESH_TOKEN_TTL_DAYS', 30)) * 24 * 60;

        return cookie(
            name: env('AUTH_REFRESH_COOKIE_NAME', 'refresh_token'),
            value: $token,
            minutes: $minutes,
            path: '/',
            domain: $this->domain(),
            secure: $this->secure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->sameSite(),
        );
    }

    public function clearAccessTokenCookie(): Cookie
    {
        return cookie()->forget(
            name: env('AUTH_ACCESS_COOKIE_NAME', 'access_token'),
            path: '/',
            domain: $this->domain(),
        );
    }

    public function clearRefreshTokenCookie(): Cookie
    {
        return cookie()->forget(
            name: env('AUTH_REFRESH_COOKIE_NAME', 'refresh_token'),
            path: '/',
            domain: $this->domain(),
        );
    }

    private function domain(): ?string
    {
        $domain = env('AUTH_COOKIE_DOMAIN');

        return ($domain === null || $domain === '' || $domain === 'null') ? null : $domain;
    }

    private function secure(): bool
    {
        return filter_var(env('AUTH_COOKIE_SECURE', false), FILTER_VALIDATE_BOOL);
    }

    private function sameSite(): string
    {
        return strtolower((string) env('AUTH_COOKIE_SAME_SITE', 'lax'));
    }
}