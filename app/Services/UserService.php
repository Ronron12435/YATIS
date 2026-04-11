<?php

namespace App\Services;

use App\DTOs\User\UpdateUserDTO;
use App\DTOs\User\UpdateLocationDTO;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use App\Responses\LocationResponse;

class UserService
{
    public function __construct(private UserRepository $userRepository) {}

    public function getAll(?string $search, ?string $role): ApiResponse
    {
        return new ApiResponse(true, $this->userRepository->search($search, $role), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new ApiResponse(false, null, 'User not found', 404);
        }

        return new ApiResponse(true, $user, 'Success');
    }

    public function update(int $id, int $authId, UpdateUserDTO $dto): ApiResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new ApiResponse(false, null, 'User not found', 404);
        }

        if ($authId !== $user->id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $data = array_filter([
            'username'        => $dto->username,
            'first_name'      => $dto->firstName,
            'last_name'       => $dto->lastName,
            'bio'             => $dto->bio,
            'profile_picture' => $dto->profilePicture,
            'cover_photo'     => $dto->coverPhoto,
            'is_private'      => $dto->isPrivate,
        ], fn($v) => $v !== null);

        $updated = $this->userRepository->update($user, $data);

        return new ApiResponse(true, $updated, 'User updated successfully');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new ApiResponse(false, null, 'User not found', 404);
        }

        if ($authId !== $user->id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->userRepository->delete($user);

        return new ApiResponse(true, null, 'User deleted successfully');
    }

    public function getPeopleMap(int $authId): ApiResponse
    {
        $users = $this->userRepository->getPeopleMap($authId);
        return new ApiResponse(true, $users->values()->all(), 'Success');
    }

    public function updateLocation(UpdateLocationDTO $dto): LocationResponse
    {
        try {
            $user = $this->userRepository->updateLocation($dto->userId, $dto->latitude, $dto->longitude);
            
            return new LocationResponse(
                success: true,
                data: [
                    'id' => $user->id,
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                    'location_updated_at' => $user->location_updated_at,
                ],
                message: 'Location updated successfully',
                statusCode: 200
            );
        } catch (\Exception $e) {
            return new LocationResponse(
                success: false,
                data: null,
                message: 'Failed to update location',
                statusCode: 400,
                errors: ['error' => $e->getMessage()]
            );
        }
    }

    public function getLocation(int $userId): LocationResponse
    {
        $location = $this->userRepository->getUserLocation($userId);
        
        if (!$location) {
            return new LocationResponse(
                success: false,
                data: null,
                message: 'User not found',
                statusCode: 404
            );
        }

        return new LocationResponse(
            success: true,
            data: $location,
            message: 'Location retrieved successfully',
            statusCode: 200
        );
    }

    public function setOnlineStatus(int $userId, string $status): ApiResponse
    {
        try {
            $user = $this->userRepository->setOnlineStatus($userId, $status);
            return new ApiResponse(
                success: true,
                data: [
                    'id' => $user->id,
                    'online_status' => $user->online_status,
                    'last_activity_at' => $user->last_activity_at,
                ],
                message: 'Online status updated',
                statusCode: 200
            );
        } catch (\Exception $e) {
            return new ApiResponse(
                success: false,
                data: null,
                message: 'Failed to update online status',
                statusCode: 400
            );
        }
    }

    public function getNearbyActiveUsers(int $userId): ApiResponse
    {
        $users = $this->userRepository->getNearbyActiveUsers($userId);
        return new ApiResponse(
            success: true,
            data: $users->values()->all(),
            message: 'Nearby active users retrieved',
            statusCode: 200
        );
    }
}
