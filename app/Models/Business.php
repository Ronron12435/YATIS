<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'category',
        'description', 'address', 'phone', 'email',
        'website', 'logo', 'cover_image', 'shop_image',
        'opening_time', 'closing_time',
        'capacity', 'available_tables', 'seats_per_table',
        'latitude', 'longitude',
        'is_open', 'is_subscribed', 'subscription_date',
        'is_verified', 'business_hours',
    ];

    protected $casts = [
        'is_open'          => 'boolean',
        'is_subscribed'    => 'boolean',
        'subscription_date'=> 'datetime',
        'latitude'         => 'float',
        'longitude'        => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class);
    }
}
