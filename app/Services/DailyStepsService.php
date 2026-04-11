<?php

namespace App\Services;

use App\Models\UserDailySteps;
use App\Responses\ApiResponse;

class DailyStepsService
{
    public function getTodaySteps(int $userId): ApiResponse
    {
        try {
            $today = now()->toDateString();
            $steps = UserDailySteps::where('user_id', $userId)
                ->whereDate('date', $today)
                ->sum('steps') ?? 0;

            return new ApiResponse(true, ['steps' => $steps], 'Success');
        } catch (\Exception $e) {
            return new ApiResponse(false, null, 'Error fetching steps: ' . $e->getMessage(), 500);
        }
    }

    public function recordSteps(int $userId, int $steps): ApiResponse
    {
        try {
            $today = now()->toDateString();
            
            $record = UserDailySteps::updateOrCreate(
                [
                    'user_id' => $userId,
                    'date' => $today,
                ],
                [
                    'steps' => $steps,
                ]
            );

            return new ApiResponse(true, $record, 'Steps recorded successfully', 201);
        } catch (\Exception $e) {
            return new ApiResponse(false, null, 'Error recording steps: ' . $e->getMessage(), 500);
        }
    }

    public function incrementSteps(int $userId, int $increment = 1): ApiResponse
    {
        try {
            $today = now()->toDateString();
            
            $record = UserDailySteps::firstOrCreate(
                [
                    'user_id' => $userId,
                    'date' => $today,
                ],
                [
                    'steps' => 0,
                ]
            );

            $record->increment('steps', $increment);

            return new ApiResponse(true, $record, 'Steps incremented successfully');
        } catch (\Exception $e) {
            return new ApiResponse(false, null, 'Error incrementing steps: ' . $e->getMessage(), 500);
        }
    }
}
