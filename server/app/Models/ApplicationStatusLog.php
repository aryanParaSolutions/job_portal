<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStatusLog extends Model
{
    protected $table = 'application_status_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'job_application_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}