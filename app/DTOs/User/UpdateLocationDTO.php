<?php

namespace App\DTOs\User;

class UpdateLocationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}
}
