<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationReview extends Model
{
    use HasFactory;

    protected $fillable = ['destination_id', 'user_id', 'rating', 'review', 'image'];

    const UPDATED_AT = null;

    public function destination()
    {
        return $this->belongsTo(TouristDestination::class, 'destination_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
