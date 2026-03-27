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
        'price', 'duration', 'is_available',
    ];

    protected $casts = ['is_available' => 'boolean'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
