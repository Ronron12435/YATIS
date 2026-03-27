<?php

namespace App\DTOs\Destination;

class CreateDestinationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $location,
        public readonly string $category,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $image = null,
    ) {}
}
