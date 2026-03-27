<?php

namespace App\Repositories;

use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GroupRepository
{
    public function getPublic(): LengthAwarePaginator
    {
        return Group::where('is_private', false)->with('creator', 'members')->latest()->paginate(15);
    }

    public function findById(int $id): ?Group
    {
        return Group::with('creator', 'members')->find($id);
    }

    public function create(array $data): Group
    {
        return Group::create($data);
    }

    public function update(Group $group, array $data): Group
    {
        $group->update($data);
        return $group->fresh();
    }

    public function delete(Group $group): void
    {
        $group->delete();
    }

    public function addMember(Group $group, int $userId): void
    {
        $group->members()->syncWithoutDetaching([$userId]);
    }

    public function removeMember(Group $group, int $userId): void
    {
        $group->members()->detach($userId);
    }

    public function getMessages(int $groupId): LengthAwarePaginator
    {
        return DB::table('group_messages as gm')
            ->join('users as u', 'u.id', '=', 'gm.sender_id')
            ->select('gm.id', 'gm.content as message', 'gm.created_at', 'u.id as user_id', 'u.username', 'u.first_name', 'u.last_name')
            ->where('gm.group_id', $groupId)
            ->oldest('gm.created_at')
            ->paginate(50);
    }

    public function sendMessage(int $groupId, int $senderId, string $message): \App\Models\GroupMessage
    {
        return \App\Models\GroupMessage::create([
            'group_id' => $groupId,
            'sender_id' => $senderId,
            'content' => $message,
        ]);
    }

    public function getUserGroups(int $userId)
    {
        // Get all groups where user is creator
        $creatorGroups = Group::where('creator_id', $userId)
            ->with('members')
            ->orderByDesc('created_at')
            ->get();

        // Get all groups where user is a member
        $memberGroups = Group::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with('members')
            ->orderByDesc('created_at')
            ->get();

        // Merge and remove duplicates
        $allGroups = $creatorGroups->merge($memberGroups)->unique('id')->values();

        // Map to array format for JSON serialization
        return $allGroups->map(function ($group) {
            return (object) [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
                'description' => $group->description ? (string) $group->description : null,
                'is_private' => (bool) $group->is_private,
                'member_limit' => (int) $group->member_limit,
                'creator_id' => (int) $group->creator_id,
                'created_at' => (string) $group->created_at,
                'member_count' => (int) $group->members->count(),
            ];
        })->sortByDesc('created_at')->values()->toArray();
    }
}
