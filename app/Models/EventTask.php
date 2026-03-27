<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = [
        'event_id', 'title', 'description',
        'task_type', 'target_value',
        'reward_points', 'reward_description',
        'qr_code', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function completions()
    {
        return $this->hasMany(UserTaskCompletion::class, 'task_id');
    }
}
