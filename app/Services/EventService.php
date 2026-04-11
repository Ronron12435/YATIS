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
            'qr_code'       => $dto->qrCode,
            'badge'         => $dto->badge,
        ]);

        return new ApiResponse(true, $task, 'Task created', 201);
    }

    public function getTasksByEvent(int $eventId, ?int $userId = null): ApiResponse
    {
        return new ApiResponse(true, $this->eventRepository->getTasksByEvent($eventId, $userId), 'Success');
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

        // Validate task completion based on task type
        $validationResult = $this->validateTaskCompletion($task, $userId, $proofData);
        if (!$validationResult['valid']) {
            return new ApiResponse(false, null, $validationResult['message'], 400);
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

    /**
     * Validate task completion based on task type
     */
    private function validateTaskCompletion($task, int $userId, ?array $proofData): array
    {
        switch ($task->task_type) {
            case 'steps':
                return $this->validateStepsTask($task, $userId, $proofData);
            case 'location':
                return $this->validateLocationTask($task, $proofData);
            case 'qr_scan':
                return $this->validateQrTask($task, $proofData);
            case 'custom':
                return $this->validateCustomTask($task, $proofData);
            default:
                return ['valid' => false, 'message' => 'Unknown task type'];
        }
    }

    /**
     * Validate steps task - check if user has walked the required steps
     */
    private function validateStepsTask($task, int $userId, ?array $proofData): array
    {
        if (!$task->target_value) {
            return ['valid' => false, 'message' => 'Steps task must have a target value'];
        }

        // Get user's daily steps from today
        $todaySteps = $this->eventRepository->getUserTodaySteps($userId);

        if ($todaySteps < $task->target_value) {
            return [
                'valid' => false,
                'message' => "You need to walk {$task->target_value} steps. Current: {$todaySteps} steps"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate location task - check if proof data contains valid location
     */
    private function validateLocationTask($task, ?array $proofData): array
    {
        if (!$proofData || !isset($proofData['latitude']) || !isset($proofData['longitude'])) {
            return ['valid' => false, 'message' => 'Location proof required (latitude and longitude)'];
        }

        return ['valid' => true];
    }

    /**
     * Validate QR scan task - check if QR code was scanned
     */
    private function validateQrTask($task, ?array $proofData): array
    {
        if (!$proofData || !isset($proofData['qr_code'])) {
            return ['valid' => false, 'message' => 'QR code scan proof required'];
        }

        // If task has no QR code set, reject the submission
        if (!$task->qr_code) {
            return ['valid' => false, 'message' => 'This task does not have a QR code configured. Please contact the admin.'];
        }

        if ($proofData['qr_code'] !== $task->qr_code) {
            return ['valid' => false, 'message' => 'Invalid QR code'];
        }

        return ['valid' => true];
    }

    /**
     * Validate custom task - check if proof data (photo/screenshot) was provided
     */
    private function validateCustomTask($task, ?array $proofData): array
    {
        if (!$proofData || !isset($proofData['proof_image'])) {
            return ['valid' => false, 'message' => 'Proof of completion required (photo or screenshot)'];
        }

        return ['valid' => true];
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
            $achievements = $this->eventRepository->getUserAchievements($userId) ?? [];
            
            // Map achievements to include badge from task
            $mappedAchievements = $achievements->map(function ($achievement) {
                return [
                    'id' => $achievement->id,
                    'title' => $achievement->task->title ?? 'Achievement',
                    'description' => $achievement->task->description ?? '',
                    'badge' => $achievement->task->badge ?? '🏆',
                    'points_earned' => $achievement->points_earned,
                    'event_id' => $achievement->event_id,
                    'task_id' => $achievement->task_id,
                ];
            });
            
            return new ApiResponse(true, [
                'achievements'   => $mappedAchievements,
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
