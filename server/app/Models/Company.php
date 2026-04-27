<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'owner_user_id',
        'industry_id',
        'name',
        'logo_path',
        'description',
        'website',
        'contact_email',
        'contact_phone',
        'location_city',
        'location_state',
        'location_country',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'verification_status',
        'verified_by',
        'verified_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}