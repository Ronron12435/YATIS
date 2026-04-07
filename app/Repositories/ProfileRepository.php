<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Post;

class ProfileRepository
{
    public function getUserProfile(int $userId): ?User
    {
        return User::with(['posts', 'businesses'])->find($userId);
    }

    public function getUserPosts(int $userId, int $page = 1, int $perPage = 5): array
    {
        $posts = Post::where('user_id', $userId)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $posts->items(),
            'total' => $posts->total(),
            'per_page' => $posts->perPage(),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
        ];
    }

    public function updateUser(int $userId, array $data): bool
    {
        return User::where('id', $userId)->update($data);
    }

    public function getUserById(int $userId): ?User
    {
        return User::with(['posts', 'businesses', 'achievements', 'friends'])->find($userId);
    }

    public function getVisitors(int $userId): array
    {
        return \Illuminate\Support\Facades\DB::table('profile_visits')
            ->join('users', 'users.id', '=', 'profile_visits.visitor_id')
            ->where('profile_visits.visited_user_id', $userId)
            ->where('profile_visits.expires_at', '>', now())
            ->orderByDesc('profile_visits.visit_time')
            ->limit(5)
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.username', 'profile_visits.visit_time as visited_at')
            ->get()
            ->toArray();
    }

    public function recordVisit(int $visitedUserId, int $visitorUserId): void
    {
        \Illuminate\Support\Facades\DB::table('profile_visits')->insert([
            'visitor_id' => $visitorUserId,
            'visited_user_id' => $visitedUserId,
            'visit_time' => now(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function getAchievements(int $userId): array
    {
        $achievement = User::with('achievements')
            ->find($userId)
            ->achievements()
            ->first();

        if (!$achievement || !$achievement->badges) {
            return [];
        }

        return $achievement->badges;
    }

    public function getUserBusinesses(int $userId): array
    {
        return User::with('businesses')
            ->find($userId)
            ->businesses()
            ->get(['id', 'name', 'business_type', 'description'])
            ->toArray();
    }
}
