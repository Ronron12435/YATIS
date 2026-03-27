<?php

namespace App\DTOs\Profile;

class UpdateProfileDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $bio = null,
        public readonly bool $isPrivate = false,
    ) {}
}
