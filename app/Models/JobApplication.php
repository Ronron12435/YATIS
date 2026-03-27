<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    // No updated_at in actual DB — only applied_at
    const UPDATED_AT = null;
    const CREATED_AT = 'applied_at';

    protected $fillable = [
        'job_posting_id', 'user_id',
        'resume', 'cover_letter',
        'status', 'interview_date',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'applied_at'     => 'datetime',
    ];

    // status: 'pending' | 'reviewed' | 'accepted' | 'rejected'

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
