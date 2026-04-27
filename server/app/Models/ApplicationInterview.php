<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationInterview extends Model
{
    protected $table = 'application_interviews';

    protected $fillable = [
        'job_application_id',
        'scheduled_by_user_id',
        'interview_at',
        'interview_mode',
        'interview_location',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'interview_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function scheduledBy()
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }
}