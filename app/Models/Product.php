<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'name', 'description', 'price', 'stock',
        'image', 'category', 'is_available'
    ];

    protected $casts = ['is_available' => 'boolean'];

    protected $appends = ['image_url'];

    protected $visible = ['id', 'business_id', 'name', 'description', 'price', 'stock', 'image', 'image_url', 'category', 'is_available', 'created_at'];

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
