<?php

namespace App\Repositories;

use App\Models\UserDailySteps;
use Carbon\Carbon;

class DailyStepsRepository
{
    public function getTodaySteps(int $userId): int
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        
        $record = UserDailySteps::where('user_id', $userId)
            ->where('date', $today)
            ->first();
        
        return $record?->steps ?? 0;
    }

    public function updateTodaySteps(int $userId, int $steps): UserDailySteps
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        
        return UserDailySteps::updateOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['steps' => $steps]
        );
    }

    public function incrementSteps(int $userId, int $increment = 1): UserDailySteps
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        
        $record = UserDailySteps::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['steps' => 0]
        );
        
        $record->increment('steps', $increment);
        
        return $record;
    }

    public function resetTodaySteps(int $userId): void
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        
        UserDailySteps::where('user_id', $userId)
            ->where('date', $today)
            ->delete();
    }
}
