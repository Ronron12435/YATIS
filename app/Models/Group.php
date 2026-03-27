<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = ['creator_id', 'name', 'description', 'avatar', 'is_private', 'member_limit'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function messages()
    {
        return $this->hasMany(GroupMessage::class);
    }
}
