<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model implements Authenticatable, CanResetPassword
{
    use HasFactory, AuthenticatableTrait, CanResetPasswordTrait, Notifiable, HasApiTokens;

    // Use the existing users table from your database
    protected $table = 'users';
    public $timestamps = true;

    protected $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name', 'role', 'bio',
        'profile_picture', 'cover_photo', 'location_name', 'latitude', 'longitude', 'is_private', 'is_premium',
        'google_id', 'oauth_provider', 'online_status', 'last_activity_at', 'location_updated_at',
        'otp_code', 'otp_expires_at', 'email_verified'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_premium' => 'boolean',
        'is_private' => 'boolean',
        'email_verified' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'last_activity_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'otp_expires_at' => 'datetime',
    ];

    // Get full name
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Relationships
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function friendships()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->where('status', 'accepted');
    }

    public function sentMessages()
    {
        return $this->hasMany(PrivateMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(PrivateMessage::class, 'recipient_id');
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function destinationReviews()
    {
        return $this->hasMany(DestinationReview::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function taskCompletions()
    {
        return $this->hasMany(UserTaskCompletion::class);
    }

    public function achievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function groupMessages()
    {
        return $this->hasMany(GroupMessage::class, 'sender_id');
    }
}
