<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'company_id',
        'posted_by_user_id',
        'title',
        'slug',
        'description',
        'salary_min',
        'salary_max',
        'currency_code',
        'location_city',
        'location_state',
        'location_country',
        'work_mode',
        'experience_level',
        'job_type',
        'vacancies',
        'application_deadline',
        'status',
        'approval_status',
        'rejection_reason',
        'is_flagged',
        'flag_reason',
        'published_at',
        'approved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'vacancies' => 'integer',
            'is_flagged' => 'boolean',
            'application_deadline' => 'date',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    public function scopePubliclyVisible($query)
    {
        return $query
            ->where('status', 'approved')
            ->where('approval_status', 'approved')
            ->whereNull('closed_at')
            ->where(function ($q) {
                $q->whereNull('application_deadline')
                  ->orWhereDate('application_deadline', '>=', now()->toDateString());
            });
    }
}