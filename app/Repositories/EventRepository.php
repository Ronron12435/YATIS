<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\UserAchievement;
use App\Models\UserTaskCompletion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EventRepository
{
    public function search(?string $search): LengthAwarePaginator
    {
        $query = Event::where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")->orWhere('description', 'like', "%$search%");
            });
        }

        return $query->latest()->paginate(15);
    }

    public function findById(int $id): ?Event
    {
        return Event::with('tasks', 'creator')->find($id);
    }

    public function create(array $data): Event
    {
        return Event::create($data);
    }

    public function update(Event $event, array $data): Event
    {
        $event->update($data);
        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    public function createTask(array $data): EventTask
    {
        return EventTask::create($data);
    }

    public function findTaskById(int $id): ?EventTask
    {
        return EventTask::find($id);
    }

    public function getTasksByEvent(int $eventId): LengthAwarePaginator
    {
        return EventTask::where('event_id', $eventId)->paginate(15);
    }

    public function deleteTask(EventTask $task): void
    {
        $task->delete();
    }

    public function findCompletion(int $userId, int $taskId): ?UserTaskCompletion
    {
        return UserTaskCompletion::where('user_id', $userId)->where('task_id', $taskId)->first();
    }

    public function createCompletion(array $data): UserTaskCompletion
    {
        return UserTaskCompletion::create($data);
    }

    public function getParticipantIds(int $eventId)
    {
        return UserTaskCompletion::where('event_id', $eventId)->distinct()->pluck('user_id');
    }

    public function getTaskParticipantIds(int $taskId)
    {
        return UserTaskCompletion::where('task_id', $taskId)->distinct()->pluck('user_id');
    }

    public function recalculateAchievements(int $userId): void
    {
        UserAchievement::where('user_id', $userId)->delete();

        $completions = UserTaskCompletion::where('user_id', $userId)->with('task.event')->get();

        foreach ($completions as $completion) {
            UserAchievement::create([
                'user_id'       => $userId,
                'event_id'      => $completion->event_id,
                'task_id'       => $completion->task_id,
                'points_earned' => $completion->points_earned,
            ]);
        }
    }

    public function getUserAchievements(int $userId): LengthAwarePaginator
    {
        return UserAchievement::where('user_id', $userId)->with('event')->latest()->paginate(15);
    }

    public function getTotalPoints(int $userId): int
    {
        return UserAchievement::where('user_id', $userId)->sum('points_earned');
    }

    public function getTasksCompleted(int $userId): int
    {
        return UserTaskCompletion::where('user_id', $userId)->count();
    }

    public function getLeaderboard(int $limit)
    {
        return DB::table('user_task_completions')
            ->join('users', 'users.id', '=', 'user_task_completions.user_id')
            ->select(
                'users.id',
                'users.username',
                'users.first_name',
                'users.last_name',
                'users.profile_picture',
                DB::raw('SUM(user_task_completions.points_earned) as total_points'),
                DB::raw('COUNT(user_task_completions.id) as tasks_completed')
            )
            ->groupBy('user_task_completions.user_id', 'users.id', 'users.username', 'users.first_name', 'users.last_name', 'users.profile_picture')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }
}
