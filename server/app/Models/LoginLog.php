<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'login_status',
        'failure_reason',
        'logged_in_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
        ];
    }
}