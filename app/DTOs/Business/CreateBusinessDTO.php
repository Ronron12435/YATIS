<?php

namespace App\DTOs\Business;

class CreateBusinessDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $businessName,
        public readonly string $businessType,
        public readonly string $address,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $description = null,
        public readonly ?string $openingTime = null,
        public readonly ?string $closingTime = null,
        public readonly ?int $capacity = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $logo = null,
        public readonly ?string $shopImage = null,
    ) {}
}
