<?php

namespace App\DTOs\User;

class UpdateUserDTO
{
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $bio = null,
        public readonly ?string $profilePicture = null,
        public readonly ?string $coverPhoto = null,
        public readonly ?bool $isPrivate = null,
    ) {}
}
