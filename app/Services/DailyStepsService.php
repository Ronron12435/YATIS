<?php

namespace App\Services;

use App\Repositories\DailyStepsRepository;
use App\Responses\ApiResponse;

class DailyStepsService
{
    public function __construct(private DailyStepsRepository $stepsRepository) {}

    public function getTodaySteps(int $userId): ApiResponse
    {
        try {
            $steps = $this->stepsRepository->getTodaySteps($userId);
            
            return new ApiResponse(
                success: true,
                data: ['steps' => $steps],
                message: 'Daily steps retrieved',
                statusCode: 200
            );
        } catch (\Exception $e) {
            return new ApiResponse(
                success: false,
                data: null,
                message: 'Failed to retrieve daily steps',
                statusCode: 500,
                errors: ['error' => $e->getMessage()]
            );
        }
    }

    public function recordSteps(int $userId, int $steps): ApiResponse
    {
        try {
            $record = $this->stepsRepository->updateTodaySteps($userId, $steps);
            
            return new ApiResponse(
                success: true,
                data: ['steps' => $record->steps],
                message: 'Steps recorded successfully',
                statusCode: 200
            );
        } catch (\Exception $e) {
            return new ApiResponse(
                success: false,
                data: null,
                message: 'Failed to record steps',
                statusCode: 500,
                errors: ['error' => $e->getMessage()]
            );
        }
    }

    public function incrementSteps(int $userId, int $increment = 1): ApiResponse
    {
        try {
            $record = $this->stepsRepository->incrementSteps($userId, $increment);
            
            return new ApiResponse(
                success: true,
                data: ['steps' => $record->steps],
                message: 'Steps incremented successfully',
                statusCode: 200
            );
        } catch (\Exception $e) {
            return new ApiResponse(
                success: false,
                data: null,
                message: 'Failed to increment steps',
                statusCode: 500,
                errors: ['error' => $e->getMessage()]
            );
        }
    }
}
