<?php

namespace App\Services;

use App\DTOs\Group\CreateGroupDTO;
use App\Repositories\GroupRepository;
use App\Responses\ApiResponse;

class GroupService
{
    public function __construct(private GroupRepository $groupRepository) {}

    public function getPublic(): ApiResponse
    {
        return new ApiResponse(true, $this->groupRepository->getPublic(), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $group = $this->groupRepository->findById($id);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        return new ApiResponse(true, $group, 'Success');
    }

    public function create(CreateGroupDTO $dto): ApiResponse
    {
        $group = $this->groupRepository->create([
            'creator_id'  => $dto->creatorId,
            'name'        => $dto->name,
            'description' => $dto->description,
            'is_private'  => $dto->isPrivate,
            'avatar'      => $dto->avatar,
            'member_limit' => 50,
        ]);

        $this->groupRepository->addMember($group, $dto->creatorId);

        return new ApiResponse(true, $group->load('creator', 'members'), 'Group created', 201);
    }

    public function update(int $id, int $authId, array $data): ApiResponse
    {
        $group = $this->groupRepository->findById($id);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        if ($authId !== $group->creator_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->groupRepository->update($group, $data), 'Group updated');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $group = $this->groupRepository->findById($id);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        if ($authId !== $group->creator_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->groupRepository->delete($group);

        return new ApiResponse(true, null, 'Group deleted');
    }

    public function addMember(int $groupId, int $userId): ApiResponse
    {
        $group = $this->groupRepository->findById($groupId);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        $this->groupRepository->addMember($group, $userId);

        return new ApiResponse(true, null, 'Member added');
    }

    public function removeMember(int $groupId, int $authId, int $userId): ApiResponse
    {
        $group = $this->groupRepository->findById($groupId);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        if ($authId !== $group->creator_id && $authId !== $userId) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->groupRepository->removeMember($group, $userId);

        return new ApiResponse(true, null, 'Member removed');
    }

    public function getMessages(int $groupId): ApiResponse
    {
        $group = $this->groupRepository->findById($groupId);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        return new ApiResponse(true, $this->groupRepository->getMessages($groupId), 'Success');
    }

    public function sendMessage(int $groupId, int $senderId, string $message): ApiResponse
    {
        $group = $this->groupRepository->findById($groupId);

        if (!$group) {
            return new ApiResponse(false, null, 'Group not found', 404);
        }

        if (!$message || trim($message) === '') {
            return new ApiResponse(false, null, 'Message cannot be empty', 400);
        }

        $msg = $this->groupRepository->sendMessage($groupId, $senderId, trim($message));

        return new ApiResponse(true, $msg, 'Message sent', 201);
    }

    public function getUserGroups(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->groupRepository->getUserGroups($userId), 'Success');
    }
}
