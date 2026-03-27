<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'event_id', 'task_id', 'points_earned',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function task()
    {
        return $this->belongsTo(EventTask::class, 'task_id');
    }
}
