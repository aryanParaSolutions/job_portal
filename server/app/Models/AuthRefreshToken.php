<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthRefreshToken extends Model
{
    protected $table = 'auth_refresh_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'family_id',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'replaced_by_token_id',
        'created_ip',
        'created_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replacedBy()
    {
        return $this->belongsTo(self::class, 'replaced_by_token_id');
    }

    public function scopeUsable($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}