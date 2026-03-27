<?php

namespace App\Services;

use App\Repositories\FriendshipRepository;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;

class FriendshipService
{
    public function __construct(
        private FriendshipRepository $friendshipRepository,
        private UserRepository $userRepository,
    ) {}

    public function getFriends(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->friendshipRepository->getFriends($userId), 'Success');
    }

    public function getRequests(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->friendshipRepository->getPendingRequests($userId), 'Success');
    }

    public function add(int $authId, int $targetId): ApiResponse
    {
        if ($authId === $targetId) {
            return new ApiResponse(false, null, 'Cannot add yourself as friend', 400);
        }

        if (!$this->userRepository->findById($targetId)) {
            return new ApiResponse(false, null, 'User not found', 404);
        }

        if ($this->friendshipRepository->findBetween($authId, $targetId)) {
            return new ApiResponse(false, null, 'Friend request already sent', 400);
        }

        $friendship = $this->friendshipRepository->create($authId, $targetId);

        return new ApiResponse(true, $friendship, 'Friend request sent', 201);
    }

    public function accept(int $authId, int $fromUserId): ApiResponse
    {
        $friendship = $this->friendshipRepository->findRequest($fromUserId, $authId);

        if (!$friendship) {
            return new ApiResponse(false, null, 'Friend request not found', 404);
        }

        return new ApiResponse(true, $this->friendshipRepository->accept($friendship), 'Friend request accepted');
    }

    public function reject(int $authId, int $fromUserId): ApiResponse
    {
        $friendship = $this->friendshipRepository->findRequest($fromUserId, $authId);

        if (!$friendship) {
            return new ApiResponse(false, null, 'Friend request not found', 404);
        }

        $this->friendshipRepository->delete($friendship);

        return new ApiResponse(true, null, 'Friend request rejected');
    }

    public function remove(int $authId, int $friendId): ApiResponse
    {
        $this->friendshipRepository->removeBoth($authId, $friendId);

        return new ApiResponse(true, null, 'Friend removed');
    }

    public function block(int $authId, int $targetId): ApiResponse
    {
        $this->friendshipRepository->block($authId, $targetId);

        return new ApiResponse(true, null, 'User blocked');
    }

    public function getStatus(int $authId, int $targetId): ApiResponse
    {
        return new ApiResponse(true, ['status' => $this->friendshipRepository->getStatus($authId, $targetId)], 'Success');
    }
}
