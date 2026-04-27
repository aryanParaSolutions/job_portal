<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApprovalLog extends Model
{
    protected $table = 'job_approval_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'job_id',
        'action_by_user_id',
        'action',
        'old_status',
        'new_status',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}