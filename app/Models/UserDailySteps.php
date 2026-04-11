<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDailySteps extends Model
{
    protected $table = 'user_daily_steps';

    protected $fillable = [
        'user_id',
        'date',
        'steps',
    ];

    protected $casts = [
        'date' => 'date',
        'steps' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
