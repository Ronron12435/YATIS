<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User|int $user, array $data): User
    {
        if (is_int($user)) {
            $user = $this->findById($user);
        }
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function search(?string $search, ?string $role): LengthAwarePaginator
    {
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                  ->orWhere('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        return $query->paginate(15);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    /**
     * Update user data.
     */
    public function updateUser(int $id, array $data): bool
    {
        return User::where('id', $id)->update($data) > 0;
    }

    public function updateLocation(int $userId, float $latitude, float $longitude): User
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        return $this->update($user, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }

    public function getUserLocation(int $userId): ?array
    {
        $user = $this->findById($userId);
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'location_updated_at' => $user->location_updated_at,
        ];
    }

    public function setOnlineStatus(int $userId, string $status): User
    {
        $user = $this->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        return $this->update($user, [
            'online_status' => $status,
            'last_activity_at' => now(),
        ]);
    }

    public function getNearbyActiveUsers(int $authId, float $radiusKm = 5): \Illuminate\Database\Eloquent\Collection
    {
        $authUser = $this->findById($authId);
        if (!$authUser || !$authUser->latitude || !$authUser->longitude) {
            \Log::warning("Auth user {$authId} has no location data");
            return collect([]);
        }

        \Log::info("Getting nearby users for {$authUser->username} at ({$authUser->latitude}, {$authUser->longitude})");

        // Use database-level distance calculation with Haversine formula
        $radiusMeters = $radiusKm * 1000;
        $earthRadiusMeters = 6371000;
        $lat = $authUser->latitude;
        $lng = $authUser->longitude;
        
        $users = User::where('id', '!=', $authId)
            ->where('role', 'user')
            ->where('online_status', 'online')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw(
                "*, ({$earthRadiusMeters} * acos(cos(radians({$lat})) * cos(radians(latitude)) * cos(radians(longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(latitude)))) as distance_meters"
            )
            ->havingRaw("distance_meters <= {$radiusMeters}")
            ->orderBy('distance_meters')
            ->get();

        \Log::info("Nearby users count: " . $users->count());
        return $users;
    }

    public function getPeopleMap(int $authId)
    {
        // Sagay City bounds (accurate)
        $sagayMinLat = 10.85;
        $sagayMaxLat = 10.94;
        $sagayMinLng = 123.38;
        $sagayMaxLng = 123.47;
        
        // Get current user's location
        $authUser = User::find($authId);
        
        if (!$authUser) {
            \Log::error("Auth user not found: {$authId}");
            return collect([]);
        }
        
        \Log::info("Getting people map for user: {$authUser->username} (ID: {$authId}, Role: {$authUser->role})");
        
        // Get only normal users (role='user'), exclude admin and business accounts
        // Show all users with coordinates (not just online)
        $users = User::where('id', '!=', $authId)
            ->where('role', 'user')  // Only normal users
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('id', 'username', 'first_name', 'last_name', 'location_name', 'latitude', 'longitude', 'online_status', 'last_activity_at')
            ->get();
        
        \Log::info("Found " . $users->count() . " normal users with coordinates (excluding admin and business)");
        foreach ($users as $u) {
            \Log::info("  - {$u->username} (Role: user, Status: {$u->online_status}): lat={$u->latitude}, lng={$u->longitude}");
        }
        
        // Get all friendships for this user in one query
        $friendships = Friendship::where(function ($q) use ($authId) {
            $q->where('user_id', $authId)->orWhere('friend_id', $authId);
        })->get()->keyBy(function ($f) use ($authId) {
            return $f->user_id === $authId ? $f->friend_id : $f->user_id;
        });
        
        return $users->map(function ($user, $index) use ($authId, $friendships, $sagayMinLat, $sagayMaxLat, $sagayMinLng, $sagayMaxLng) {
            $friendship = $friendships->get($user->id);
            
            $status = 'none';
            if ($friendship) {
                if ($friendship->status === 'accepted') {
                    $status = 'friends';
                } elseif ($friendship->user_id === $authId && $friendship->status === 'pending') {
                    $status = 'request_sent';
                } elseif ($friendship->friend_id === $authId && $friendship->status === 'pending') {
                    $status = 'request_received';
                }
            }
            
            // Use actual coordinates from database
            $latitude = (float) ($user->latitude ?? 10.8967);
            $longitude = (float) ($user->longitude ?? 123.4253);
            
            \Log::info("User {$user->username} (ID: {$user->id}): lat={$latitude}, lng={$longitude}, status={$status}");
            
            return [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'location_name' => $user->location_name ?? 'Nearby',
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'friendship_status' => $status,
                'online_status' => $user->online_status ?? 'offline',
                'last_activity_at' => $user->last_activity_at,
            ];
        });
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c;
    }
}
