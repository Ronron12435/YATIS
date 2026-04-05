<?php

namespace App\Services;

use App\DTOs\Message\SendGroupMessageDTO;
use App\DTOs\Message\SendMessageDTO;
use App\Repositories\MessageRepository;
use App\Responses\ApiResponse;

class MessageService
{
    public function __construct(private MessageRepository $messageRepository) {}

    public function getConversation(int $authId, int $userId): ApiResponse
    {
        $messages = $this->messageRepository->getConversation($authId, $userId);
        $this->messageRepository->markConversationRead($authId, $userId);

        return new ApiResponse(true, $messages, 'Success');
    }

    public function send(SendMessageDTO $dto): ApiResponse
    {
        $message = $this->messageRepository->createPrivate([
            'sender_id'   => $dto->senderId,
            'recipient_id' => $dto->recipientId,
            'message'     => $dto->content,
        ]);

        return new ApiResponse(true, $message, 'Message sent', 201);
    }

    public function delete(int $messageId, int $authId): ApiResponse
    {
        $message = $this->messageRepository->findPrivateById($messageId);

        if (!$message) {
            return new ApiResponse(false, null, 'Message not found', 404);
        }

        if ($authId !== $message->sender_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->messageRepository->deletePrivate($message);

        return new ApiResponse(true, null, 'Message deleted');
    }

    public function unreadCount(int $userId): ApiResponse
    {
        return new ApiResponse(true, ['unread_count' => $this->messageRepository->unreadCount($userId)], 'Success');
    }

    public function markAsRead(int $messageId, int $authId): ApiResponse
    {
        $message = $this->messageRepository->findPrivateById($messageId);

        if (!$message) {
            return new ApiResponse(false, null, 'Message not found', 404);
        }

        if ($authId !== $message->recipient_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->messageRepository->markPrivateRead($message), 'Marked as read');
    }

    public function sendGroupMessage(SendGroupMessageDTO $dto): ApiResponse
    {
        if (!$this->messageRepository->isGroupMember($dto->groupId, $dto->senderId)) {
            return new ApiResponse(false, null, 'Not a member of this group', 403);
        }

        $message = $this->messageRepository->createGroupMessage([
            'group_id' => $dto->groupId,
            'user_id'  => $dto->senderId,
            'message'  => $dto->content,
        ]);

        return new ApiResponse(true, $message, 'Message sent', 201);
    }

    public function getGroupMessages(int $groupId): ApiResponse
    {
        return new ApiResponse(true, $this->messageRepository->getGroupMessages($groupId), 'Success');
    }

    public function markGroupAsRead(int $messageId): ApiResponse
    {
        $message = $this->messageRepository->findGroupMessageById($messageId);

        if (!$message) {
            return new ApiResponse(false, null, 'Message not found', 404);
        }

        return new ApiResponse(true, $this->messageRepository->markGroupRead($message), 'Marked as read');
    }

    public function getGroupUnreadCounts(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->messageRepository->getGroupUnreadCounts($userId), 'Success');
    }
}
