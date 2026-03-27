<?php

namespace App\DTOs\Event;

class CreateTaskDTO
{
    public function __construct(
        public readonly int $eventId,
        public readonly string $title,
        public readonly string $taskType,
        public readonly int $rewardPoints,
        public readonly ?string $description = null,
        public readonly ?int $targetValue = null,
    ) {}
}
