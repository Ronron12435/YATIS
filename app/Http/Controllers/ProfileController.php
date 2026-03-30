<?php

namespace App\Http\Controllers;

use App\DTOs\Profile\UpdateProfileDTO;
use App\DTOs\Profile\ChangePasswordDTO;
use App\DTOs\Profile\CreatePostDTO;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profileService) {}

    public function show(int $id)
    {
        $response = $this->profileService->getProfile($id);
        return response()->json($response, $response['success'] ? 200 : 404);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
            'is_private' => 'nullable|boolean',
        ]);

        $dto = new UpdateProfileDTO(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            bio: $validated['bio'] ?? null,
            isPrivate: $validated['is_private'] ?? false,
        );

        $response = $this->profileService->updateProfile(auth()->id(), $dto);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|string|min:8',
        ]);

        $dto = new ChangePasswordDTO(
            currentPassword: $validated['current_password'],
            newPassword: $validated['new_password'],
            confirmPassword: $validated['confirm_password'],
        );

        $response = $this->profileService->changePassword(auth()->id(), $dto);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'privacy' => 'nullable|in:public,friends,private',
            'image' => 'nullable|string',
        ]);

        $dto = new CreatePostDTO(
            content: $validated['content'],
            privacy: $validated['privacy'] ?? 'public',
            image: $validated['image'] ?? null,
        );

        $response = $this->profileService->createPost(auth()->id(), $dto);
        return response()->json($response, $response['success'] ? 201 : 400);
    }

    public function deletePost(int $postId)
    {
        $response = $this->profileService->deletePost($postId, auth()->id());
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function getPosts(Request $request, ?int $userId = null)
    {
        $page = $request->query('page', 1);
        $actualUserId = $userId ?? auth()->id();
        $response = $this->profileService->getUserPosts($actualUserId, $page);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function current()
    {
        $response = $this->profileService->getProfile(auth()->id());
        return response()->json($response, $response['success'] ? 200 : 404);
    }

    public function getVisitors(?int $userId = null)
    {
        $actualUserId = $userId ?? auth()->id();
        $response = $this->profileService->getVisitors($actualUserId);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function recordVisit(int $userId)
    {
        $response = $this->profileService->recordVisit($userId, auth()->id());
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'required|image|max:5120',
        ]);

        $response = $this->profileService->uploadAvatar(auth()->id(), $validated['avatar']);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function deleteAvatar()
    {
        $response = $this->profileService->deleteAvatar(auth()->id());
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function uploadCover(Request $request)
    {
        $validated = $request->validate([
            'cover' => 'required|image|max:5120',
        ]);

        $response = $this->profileService->uploadCover(auth()->id(), $validated['cover']);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function deleteCover()
    {
        $response = $this->profileService->deleteCover(auth()->id());
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function getAchievements(?int $userId = null)
    {
        $actualUserId = $userId ?? auth()->id();
        $response = $this->profileService->getAchievements($actualUserId);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function businesses(int $userId)
    {
        $response = $this->profileService->getUserBusinesses($userId);
        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function updateLocation(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = auth()->user();
        $user->update([
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
            ],
        ]);
    }
}
