<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    // No updated_at in actual DB
    const UPDATED_AT = null;

    protected $fillable = ['group_id', 'sender_id', 'content'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
