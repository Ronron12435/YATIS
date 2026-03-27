<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TouristDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'location', 'address', 'category',
        'latitude', 'longitude', 'image',
        'rating', 'reviews_count',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
    ];

    public function reviews()
    {
        return $this->hasMany(DestinationReview::class, 'destination_id');
    }
}
