<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'table_number',
        'capacity', 'status', 'reserved_until',
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
