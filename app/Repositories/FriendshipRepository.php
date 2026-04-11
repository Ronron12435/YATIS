<?php

namespace App\Repositories;

use App\Models\Friendship;
use Illuminate\Support\Facades\DB;

class FriendshipRepository
{
    public function getFriends(int $userId)
    {
        // Get friendships where user is the initiator
        $friendsAsInitiator = DB::table('friendships as f')
            ->join('users as u', 'u.id', '=', 'f.friend_id')
            ->where('f.user_id', $userId)
            ->where('f.status', 'accepted')
            ->select('u.id', 'u.username', 'u.first_name', 'u.last_name', 'u.profile_picture');

        // Get friendships where user is the recipient
        $friendsAsRecipient = DB::table('friendships as f')
            ->join('users as u', 'u.id', '=', 'f.user_id')
            ->where('f.friend_id', $userId)
            ->where('f.status', 'accepted')
            ->select('u.id', 'u.username', 'u.first_name', 'u.last_name', 'u.profile_picture');

        // Union and get results
        $union = $friendsAsInitiator->union($friendsAsRecipient);
        $results = DB::table(DB::raw("({$union->toSql()}) as friends"))
            ->mergeBindings($union)
            ->orderBy('first_name')
            ->get();

        return $results;
    }

    public function getPendingRequests(int $userId)
    {
        // Use indexed columns (friend_id, status) for faster query
        // Join with users table to get user details
        return DB::table('friendships as f')
            ->join('users as u', 'u.id', '=', 'f.user_id')
            ->where('f.friend_id', $userId)
            ->where('f.status', 'pending')
            ->select('u.id', 'u.username', 'u.first_name', 'u.last_name', 'u.profile_picture')
            ->orderBy('f.created_at', 'desc')
            ->get();
    }

    public function findBetween(int $userId, int $friendId): ?Friendship
    {
        return Friendship::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();
    }

    public function findRequest(int $fromUserId, int $toUserId): ?Friendship
    {
        return Friendship::where('friend_id', $toUserId)->where('user_id', $fromUserId)->first();
    }

    public function create(int $userId, int $friendId): Friendship
    {
        return Friendship::create(['user_id' => $userId, 'friend_id' => $friendId, 'status' => 'pending']);
    }

    public function accept(Friendship $friendship): Friendship
    {
        $friendship->update(['status' => 'accepted']);
        return $friendship->fresh();
    }

    public function delete(Friendship $friendship): void
    {
        $friendship->delete();
    }

    public function removeBoth(int $userId, int $friendId): void
    {
        Friendship::where('user_id', $userId)->where('friend_id', $friendId)->delete();
        Friendship::where('user_id', $friendId)->where('friend_id', $userId)->delete();
    }

    public function block(int $userId, int $friendId): void
    {
        Friendship::updateOrCreate(
            ['user_id' => $userId, 'friend_id' => $friendId],
            ['status' => 'blocked']
        );
    }

    public function getStatus(int $userId, int $friendId): string
    {
        $friendship = $this->findBetween($userId, $friendId);
        return $friendship ? $friendship->status : 'none';
    }

    public function areFriends(int $userId, int $friendId): bool
    {
        return Friendship::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->where('status', 'accepted')->exists();
    }
}
