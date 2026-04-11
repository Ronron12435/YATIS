<?php

namespace App\Http\Controllers;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\CreateTaskDTO;
use App\Services\EventService;
use App\Services\DailyStepsService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private EventService $eventService,
        private DailyStepsService $stepsService
    ) {}

    public function index(Request $request)
    {
        $response = $this->eventService->getAll($request->input('search'));
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'image'       => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('events', 'public');
        }

        $dto = new CreateEventDTO(
            createdBy: $request->user()->id,
            title: $validated['title'],
            description: $validated['description'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            image: $imagePath,
        );

        $response = $this->eventService->create($dto, $request->user()->role);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->eventService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'       => 'string|max:255',
            'description' => 'string',
            'start_date'  => 'date',
            'end_date'    => 'date|after:start_date',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $response = $this->eventService->update((int) $id, $request->user()->id, $request->user()->role, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->eventService->delete((int) $id, $request->user()->id, $request->user()->role);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function createTask(Request $request, $eventId)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'task_type'     => 'required|in:steps,location,qr_scan,custom',
            'reward_points' => 'required|integer|min:1',
            'target_value'  => 'nullable|integer|min:1',
            'qr_code'       => 'nullable|string|max:255',
            'badge'         => 'nullable|string|max:255',
        ]);

        $dto = new CreateTaskDTO(
            eventId: (int) $eventId,
            title: $validated['title'],
            taskType: $validated['task_type'],
            rewardPoints: $validated['reward_points'],
            description: $validated['description'] ?? null,
            targetValue: $validated['target_value'] ?? null,
            qrCode: $validated['qr_code'] ?? null,
            badge: $validated['badge'] ?? null,
        );

        $response = $this->eventService->createTask($dto, $request->user()->id, $request->user()->role);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function eventTasks(Request $request, $eventId)
    {
        $response = $this->eventService->getTasksByEvent((int) $eventId, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function completeTask(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_id'   => 'required|integer|exists:events,id',
                'task_id'    => 'required|integer|exists:event_tasks,id',
                'proof_data' => 'nullable|array',
            ]);

            $response = $this->eventService->completeTask(
                $request->user()->id,
                $validated['event_id'],
                $validated['task_id'],
                $validated['proof_data'] ?? null,
            );

            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Task completion validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Task completion error', [
                'message' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteTask(Request $request, $taskId)
    {
        $response = $this->eventService->deleteTask((int) $taskId, $request->user()->id, $request->user()->role);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function userAchievements(Request $request)
    {
        $userId = $request->input('user_id', $request->user()->id);
        $response = $this->eventService->getUserAchievements((int) $userId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function leaderboard(Request $request)
    {
        $response = $this->eventService->getLeaderboard((int) $request->input('limit', 50));
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getTodaySteps(Request $request)
    {
        $response = $this->stepsService->getTodaySteps($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function recordSteps(Request $request)
    {
        $validated = $request->validate([
            'steps' => 'required|integer|min:0',
        ]);

        $response = $this->stepsService->recordSteps($request->user()->id, $validated['steps']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function incrementSteps(Request $request)
    {
        $validated = $request->validate([
            'increment' => 'nullable|integer|min:1',
        ]);

        $response = $this->stepsService->incrementSteps($request->user()->id, $validated['increment'] ?? 1);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
