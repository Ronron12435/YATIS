<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = [
        'business_id', 'name', 'description',
        'price', 'duration', 'is_available', 'image',
    ];

    protected $casts = ['is_available' => 'boolean'];

    protected $appends = ['image_url'];

    protected $visible = ['id', 'business_id', 'name', 'description', 'price', 'duration', 'image', 'image_url', 'is_available', 'created_at'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        $image = (string) $this->image;
        
        if (str_starts_with($image, 'http')) {
            return $image;
        }

        if (str_starts_with($image, '/storage/')) {
            return $image;
        }

        return '/storage/' . $image;
    }
}
