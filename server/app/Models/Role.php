<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}