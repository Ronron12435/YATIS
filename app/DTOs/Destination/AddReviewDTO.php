<?php

namespace App\DTOs\Destination;

class AddReviewDTO
{
    public function __construct(
        public readonly int $destinationId,
        public readonly int $userId,
        public readonly int $rating,
        public readonly string $review,
        public readonly ?string $image = null,
    ) {}
}
