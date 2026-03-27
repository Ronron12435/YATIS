<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = [
        'business_id', 'table_number',
        'seats', 'is_occupied', 'occupied_at',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'occupied_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
