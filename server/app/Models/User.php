<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'role_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'status',
        'last_login_ip',
        'remember_token',
        'profile_photo',
        'is_deleted',
        'email_verified_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $appends = [
        'full_name',
        'role_slug',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_deleted' => 'boolean',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function refreshTokens()
    {
        return $this->hasMany(AuthRefreshToken::class, 'user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function getRoleSlugAttribute(): ?string
    {
        return $this->role?->slug;
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->role?->slug === $roleSlug;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (bool) $this->is_deleted === false;
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked' || (bool) $this->is_deleted === true;
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', 'active')
            ->where('is_deleted', 0);
    }
}