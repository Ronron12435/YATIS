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

    public function update(User $user, array $data): User
    {
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

    public function getPeopleMap(int $authId)
    {
        // Sagay City bounds
        $sagayMinLat = 10.75;
        $sagayMaxLat = 11.05;
        $sagayMinLng = 123.30;
        $sagayMaxLng = 123.55;
        $sagayCenter = ['lat' => 10.8967, 'lng' => 123.4253];
        
        // Get current user's location
        $authUser = User::find($authId);
        
        if (!$authUser) {
            return collect([]);
        }
        
        // Get ALL users except auth user
        // Show everyone: regular users, employers, business owners, admins, etc.
        $users = User::where('id', '!=', $authId)
            ->select('id', 'username', 'first_name', 'last_name', 'location_name', 'latitude', 'longitude')
            ->get();
        
        // Get all friendships for this user in one query
        $friendships = Friendship::where(function ($q) use ($authId) {
            $q->where('user_id', $authId)->orWhere('friend_id', $authId);
        })->get()->keyBy(function ($f) use ($authId) {
            return $f->user_id === $authId ? $f->friend_id : $f->user_id;
        });
        
        return $users->map(function ($user, $index) use ($authId, $friendships, $sagayMinLat, $sagayMaxLat, $sagayMinLng, $sagayMaxLng, $sagayCenter) {
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
            
            // Ensure coordinates are within Sagay City bounds
            $latitude = $user->latitude ?? null;
            $longitude = $user->longitude ?? null;
            
            // If no coordinates or outside Sagay City, generate random coordinates within Sagay City
            if (!$latitude || !$longitude || $latitude < $sagayMinLat || $latitude > $sagayMaxLat || $longitude < $sagayMinLng || $longitude > $sagayMaxLng) {
                // Use user ID as seed for consistent but distributed coordinates
                mt_srand($user->id);
                $latitude = $sagayCenter['lat'] + (mt_rand(-500, 500) / 10000);
                $longitude = $sagayCenter['lng'] + (mt_rand(-500, 500) / 10000);
            }
            
            return [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'location_name' => $user->location_name ?? 'Nearby',
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'friendship_status' => $status,
            ];
        });
    }
}
