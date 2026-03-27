<?php

namespace App\Services;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\CreateTaskDTO;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;

class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
    ) {}

    public function getAll(?string $search): ApiResponse
    {
        return new ApiResponse(true, $this->eventRepository->search($search), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $event = $this->eventRepository->findById($id);

        if (!$event) {
            return new ApiResponse(false, null, 'Event not found', 404);
        }

        return new ApiResponse(true, $event, 'Success');
    }

    public function create(CreateEventDTO $dto, string $role): ApiResponse
    {
        if ($role !== 'admin') {
            return new ApiResponse(false, null, 'Only admins can create events', 403);
        }

        $event = $this->eventRepository->create([
            'created_by'  => $dto->createdBy,
            'title'       => $dto->title,
            'description' => $dto->description,
            'start_date'  => $dto->startDate,
            'end_date'    => $dto->endDate,
            'image'       => $dto->image,
            'is_active'   => true,
        ]);

        return new ApiResponse(true, $event, 'Event created', 201);
    }

    public function update(int $id, int $authId, string $role, array $data): ApiResponse
    {
        $event = $this->eventRepository->findById($id);

        if (!$event) {
            return new ApiResponse(false, null, 'Event not found', 404);
        }

        if ($authId !== $event->created_by && $role !== 'admin') {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->eventRepository->update($event, $data), 'Event updated');
    }

    public function delete(int $id, int $authId, string $role): ApiResponse
    {
        $event = $this->eventRepository->findById($id);

        if (!$event) {
            return new ApiResponse(false, null, 'Event not found', 404);
        }

        if ($authId !== $event->created_by && $role !== 'admin') {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $userIds = $this->eventRepository->getParticipantIds($id);
        $this->eventRepository->delete($event);

        foreach ($userIds as $userId) {
            $this->eventRepository->recalculateAchievements($userId);
        }

        return new ApiResponse(true, null, 'Event deleted');
    }

    public function createTask(CreateTaskDTO $dto, int $authId, string $role): ApiResponse
    {
        $event = $this->eventRepository->findById($dto->eventId);

        if (!$event) {
            return new ApiResponse(false, null, 'Event not found', 404);
        }

        if ($authId !== $event->created_by && $role !== 'admin') {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $task = $this->eventRepository->createTask([
            'event_id'      => $dto->eventId,
            'title'         => $dto->title,
            'description'   => $dto->description,
            'task_type'     => $dto->taskType,
            'reward_points' => $dto->rewardPoints,
            'target_value'  => $dto->targetValue,
        ]);

        return new ApiResponse(true, $task, 'Task created', 201);
    }

    public function getTasksByEvent(int $eventId): ApiResponse
    {
        return new ApiResponse(true, $this->eventRepository->getTasksByEvent($eventId), 'Success');
    }

    public function completeTask(int $userId, int $eventId, int $taskId, ?array $proofData): ApiResponse
    {
        if ($this->eventRepository->findCompletion($userId, $taskId)) {
            return new ApiResponse(false, null, 'Task already completed', 400);
        }

        $task = $this->eventRepository->findTaskById($taskId);

        if (!$task) {
            return new ApiResponse(false, null, 'Task not found', 404);
        }

        $completion = $this->eventRepository->createCompletion([
            'user_id'       => $userId,
            'event_id'      => $eventId,
            'task_id'       => $taskId,
            'proof_data'    => json_encode($proofData ?? []),
            'points_earned' => $task->reward_points,
        ]);

        $this->eventRepository->recalculateAchievements($userId);

        return new ApiResponse(true, $completion, 'Task completed', 201);
    }

    public function deleteTask(int $taskId, int $authId, string $role): ApiResponse
    {
        $task = $this->eventRepository->findTaskById($taskId);

        if (!$task) {
            return new ApiResponse(false, null, 'Task not found', 404);
        }

        $event = $this->eventRepository->findById($task->event_id);

        if ($authId !== $event->created_by && $role !== 'admin') {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $userIds = $this->eventRepository->getTaskParticipantIds($taskId);
        $this->eventRepository->deleteTask($task);

        foreach ($userIds as $userId) {
            $this->eventRepository->recalculateAchievements($userId);
        }

        return new ApiResponse(true, null, 'Task deleted');
    }

    public function getUserAchievements(int $userId): ApiResponse
    {
        try {
            return new ApiResponse(true, [
                'achievements'   => $this->eventRepository->getUserAchievements($userId) ?? [],
                'total_points'   => $this->eventRepository->getTotalPoints($userId) ?? 0,
                'tasks_completed'=> $this->eventRepository->getTasksCompleted($userId) ?? 0,
            ], 'Success');
        } catch (\Exception $e) {
            return new ApiResponse(true, [
                'achievements'   => [],
                'total_points'   => 0,
                'tasks_completed'=> 0,
            ], 'Success');
        }
    }

    public function getLeaderboard(int $limit): ApiResponse
    {
        return new ApiResponse(true, $this->eventRepository->getLeaderboard($limit), 'Success');
    }
}
