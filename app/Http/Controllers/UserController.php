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
}
