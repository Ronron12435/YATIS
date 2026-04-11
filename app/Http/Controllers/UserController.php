<?php

namespace App\Http\Controllers;

use App\DTOs\User\UpdateUserDTO;
use App\DTOs\User\UpdateLocationDTO;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request)
    {
        $response = $this->userService->getAll(
            $request->input('search'),
            $request->input('role'),
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->userService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'username'        => 'string|max:255',
            'first_name'      => 'string|max:255',
            'last_name'       => 'string|max:255',
            'bio'             => 'nullable|string',
            'profile_picture' => 'nullable|image|max:2048',
            'cover_photo'     => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile_photos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $request->file('cover_photo')->store('profile_photos', 'public');
        }

        $dto = new UpdateUserDTO(
            username: $validated['username'] ?? null,
            firstName: $validated['first_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            bio: $validated['bio'] ?? null,
            profilePicture: $validated['profile_picture'] ?? null,
            coverPhoto: $validated['cover_photo'] ?? null,
        );

        $response = $this->userService->update((int) $id, $request->user()->id, $dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->userService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function peopleMap(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        
        $response = $this->userService->getPeopleMap(auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function checkRoles()
    {
        $users = User::select('id', 'username', 'first_name', 'last_name', 'role', 'latitude', 'longitude')->get();
        $roleCount = $users->groupBy('role')->map->count();
        
        return response()->json([
            'total_users' => $users->count(),
            'users' => $users,
            'role_breakdown' => $roleCount
        ]);
    }

    public function setUserCoordinates()
    {
        $userCoordinates = [
            'Kelir' => ['lat' => 10.8961, 'lng' => 123.4155],
            'Dako' => ['lat' => 10.898, 'lng' => 123.4162],
            'Jayson' => ['lat' => 10.8950, 'lng' => 123.4180],
        ];

        $defaultLat = 10.8967;
        $defaultLng = 123.4253;

        $users = User::where('role', 'user')->get();
        $updated = [];

        foreach ($users as $user) {
            $lat = $defaultLat;
            $lng = $defaultLng;
            
            // Check if user has specific coordinates
            if (isset($userCoordinates[$user->username])) {
                $lat = $userCoordinates[$user->username]['lat'];
                $lng = $userCoordinates[$user->username]['lng'];
            } else {
                // Spread other users around the default location
                $offset = count($updated) * 0.0015;
                $lat = $defaultLat + $offset;
                $lng = $defaultLng + $offset;
            }
            
            $user->update([
                'latitude' => $lat,
                'longitude' => $lng,
                'location_updated_at' => now(),
            ]);
            
            $updated[] = [
                'id' => $user->id,
                'username' => $user->username,
                'latitude' => $lat,
                'longitude' => $lng,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'User coordinates updated',
            'total_updated' => count($updated),
            'updated' => $updated
        ]);
    }

    public function fixUserRoles()
    {
        // Set all users to 'user' role except admin (ID 7)
        User::where('id', '!=', 7)->update(['role' => 'user']);
        
        $users = User::select('id', 'username', 'first_name', 'last_name', 'role')->get();
        $roleCount = $users->groupBy('role')->map->count();
        
        return response()->json([
            'success' => true,
            'message' => 'User roles fixed',
            'total_users' => $users->count(),
            'role_breakdown' => $roleCount
        ]);
    }

    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $dto = new UpdateLocationDTO(
            userId: $request->user()->id,
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
        );

        $response = $this->userService->updateLocation($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getLocation(Request $request)
    {
        $response = $this->userService->getLocation($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function businesses(Request $request)
    {
        $user = $request->user();
        $businesses = $user->businesses()->get(['id', 'name', 'category', 'address']);
        
        return response()->json([
            'success' => true,
            'data' => $businesses
        ]);
    }

    public function setOnlineStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:online,away,offline',
        ]);

        $response = $this->userService->setOnlineStatus($request->user()->id, $validated['status']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getNearbyActiveUsers(Request $request)
    {
        $response = $this->userService->getNearbyActiveUsers($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode)
            ->header('Cache-Control', 'private, max-age=5');
    }

    public function debugPeopleMap(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $authId = auth()->id();
        $authUser = User::find($authId);

        // Get all users with role='user'
        $allUsers = User::where('role', 'user')->select('id', 'username', 'first_name', 'last_name', 'role', 'online_status', 'latitude', 'longitude', 'last_activity_at')->get();

        // Get online users
        $onlineUsers = User::where('role', 'user')->where('online_status', 'online')->select('id', 'username', 'first_name', 'last_name', 'role', 'online_status', 'latitude', 'longitude', 'last_activity_at')->get();

        // Get users with coordinates
        $usersWithCoords = User::where('role', 'user')->whereNotNull('latitude')->whereNotNull('longitude')->select('id', 'username', 'first_name', 'last_name', 'role', 'online_status', 'latitude', 'longitude', 'last_activity_at')->get();

        // Get the actual people map response
        $response = $this->userService->getPeopleMap($authId);

        return response()->json([
            'success' => true,
            'auth_user' => [
                'id' => $authUser->id,
                'username' => $authUser->username,
                'role' => $authUser->role,
                'online_status' => $authUser->online_status,
                'latitude' => $authUser->latitude,
                'longitude' => $authUser->longitude,
            ],
            'all_users_count' => $allUsers->count(),
            'all_users' => $allUsers,
            'online_users_count' => $onlineUsers->count(),
            'online_users' => $onlineUsers,
            'users_with_coords_count' => $usersWithCoords->count(),
            'users_with_coords' => $usersWithCoords,
            'people_map_response' => $response->toArray(),
        ]);
    }
}
