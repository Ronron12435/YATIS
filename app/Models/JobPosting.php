<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id', 'business_id',
        'title', 'position', 'description', 'requirements',
        'salary_range', 'location', 'job_type', 'status',
    ];

    // status: 'open' | 'closed'

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }
}
