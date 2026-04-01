<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailySteps extends Model
{
    protected $table = 'user_daily_steps';

    protected $fillable = ['user_id', 'date', 'steps'];

    protected $casts = [
        'date' => 'date',
        'steps' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
