<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = [
        'created_by', 'title', 'description',
        'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(EventTask::class);
    }

    public function completions()
    {
        return $this->hasMany(UserTaskCompletion::class);
    }
}
