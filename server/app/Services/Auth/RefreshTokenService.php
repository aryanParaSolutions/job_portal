<?php

namespace App\Services\Auth;

use App\Models\AuthRefreshToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RefreshTokenService
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function createForUser(User $user, string $ip = null, string $userAgent = null, ?string $familyId = null): array
    {
        $plainText = $this->tokenService->issueRefreshTokenPlainText();
        $hash = $this->tokenService->hashRefreshToken($plainText);

        $record = AuthRefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'family_id' => $familyId ?: $this->tokenService->generateFamilyId(),
            'expires_at' => Carbon::now()->addDays((int) env('AUTH_REFRESH_TOKEN_TTL_DAYS', 30)),
            'last_used_at' => null,
            'revoked_at' => null,
            'replaced_by_token_id' => null,
            'created_ip' => $ip,
            'created_user_agent' => $userAgent,
        ]);

        return [
            'plain_text' => $plainText,
            'record' => $record,
        ];
    }

    public function findUsableByPlainText(string $plainText): ?AuthRefreshToken
    {
        $hash = $this->tokenService->hashRefreshToken($plainText);

        return AuthRefreshToken::with('user.role')
            ->where('token_hash', $hash)
            ->usable()
            ->first();
    }

    public function rotate(AuthRefreshToken $currentToken, string $ip = null, string $userAgent = null): array
    {
        return DB::transaction(function () use ($currentToken, $ip, $userAgent) {
            $currentToken->refresh();

            if ($currentToken->revoked_at !== null || $currentToken->expires_at->isPast()) {
                throw new \RuntimeException('Refresh token is no longer valid.');
            }

            $newToken = $this->createForUser(
                user: $currentToken->user,
                ip: $ip,
                userAgent: $userAgent,
                familyId: $currentToken->family_id
            );

            $currentToken->update([
                'last_used_at' => now(),
                'revoked_at' => now(),
                'replaced_by_token_id' => $newToken['record']->id,
            ]);

            return $newToken;
        });
    }

    public function revokeByPlainText(?string $plainText): void
    {
        if (! $plainText) {
            return;
        }

        $hash = $this->tokenService->hashRefreshToken($plainText);

        AuthRefreshToken::where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'last_used_at' => now(),
            ]);
    }
}