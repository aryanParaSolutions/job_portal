<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateResume extends Model
{
    protected $table = 'candidate_resumes';

    public $timestamps = false;

    protected $fillable = [
        'candidate_profile_id',
        'file_name',
        'file_path',
        'file_mime_type',
        'file_size_bytes',
        'storage_disk',
        'is_current',
        'version_no',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'file_size_bytes' => 'integer',
            'version_no' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function candidateProfile()
    {
        return $this->belongsTo(CandidateProfile::class, 'candidate_profile_id');
    }
}