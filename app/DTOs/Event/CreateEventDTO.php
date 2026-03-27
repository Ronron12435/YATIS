<?php

namespace App\DTOs\Event;

class CreateEventDTO
{
    public function __construct(
        public readonly int $createdBy,
        public readonly string $title,
        public readonly string $description,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $image = null,
    ) {}
}
