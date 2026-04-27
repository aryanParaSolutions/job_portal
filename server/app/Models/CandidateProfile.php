<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateProfile extends Model
{
    protected $table = 'candidate_profiles';

    protected $fillable = [
        'user_id',
        'headline',
        'summary',
        'location_city',
        'location_state',
        'location_country',
        'profile_visibility',
        'profile_completion_percentage',
        'total_experience_years',
        'current_salary',
        'expected_salary',
        'job_type_preference',
        'preferred_work_mode',
    ];

    protected function casts(): array
    {
        return [
            'profile_completion_percentage' => 'decimal:2',
            'total_experience_years' => 'decimal:1',
            'current_salary' => 'decimal:2',
            'expected_salary' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resumes()
{
    return $this->hasMany(CandidateResume::class, 'candidate_profile_id');
}
}