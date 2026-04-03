<?php

namespace App\Repositories;

use App\Models\GroupMessage;
use App\Models\PrivateMessage;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    public function getConversation(int $authId, int $userId)
    {
        return PrivateMessage::where(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $authId);
        })->oldest()->get();
    }

    public function markConversationRead(int $receiverId, int $senderId): void
    {
        PrivateMessage::where('receiver_id', $receiverId)
            ->where('sender_id', $senderId)
            ->update(['is_read' => true]);
    }

    public function createPrivate(array $data): PrivateMessage
    {
        return PrivateMessage::create($data);
    }

    public function findPrivateById(int $id): ?PrivateMessage
    {
        return PrivateMessage::find($id);
    }

    public function deletePrivate(PrivateMessage $message): void
    {
        $message->delete();
    }

    public function unreadCount(int $userId): int
    {
        try {
            return PrivateMessage::where('receiver_id', $userId)->where('is_read', false)->count();
        } catch (\Exception $e) {
            // If receiver_id column doesn't exist, return 0
            return 0;
        }
    }

    public function markPrivateRead(PrivateMessage $message): PrivateMessage
    {
        $message->update(['is_read' => true]);
        return $message->fresh();
    }

    public function createGroupMessage(array $data): GroupMessage
    {
        return GroupMessage::create($data)->load('sender');
    }

    public function getGroupMessages(int $groupId)
    {
        return GroupMessage::where('group_id', $groupId)
            ->with(['sender' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'profile_picture', 'username');
            }])
            ->oldest()
            ->get();
    }

    public function findGroupMessageById(int $id): ?GroupMessage
    {
        return GroupMessage::find($id);
    }

    public function markGroupRead(GroupMessage $message): GroupMessage
    {
        $message->update(['is_read' => true]);
        return $message->fresh();
    }

    public function getGroupUnreadCounts(int $userId)
    {
        return DB::table('group_messages as gm')
            ->join('group_user as gu', 'gu.group_id', '=', 'gm.group_id')
            ->where('gu.user_id', $userId)
            ->where('gm.sender_id', '!=', $userId)
            ->where('gm.is_read', false)
            ->select('gm.group_id', DB::raw('COUNT(*) as unread_count'))
            ->groupBy('gm.group_id')
            ->get();
    }

    public function isGroupMember(int $groupId, int $userId): bool
    {
        return DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
    }
}
