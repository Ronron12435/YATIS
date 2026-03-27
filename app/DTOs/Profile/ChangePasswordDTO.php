<?php

namespace App\DTOs\Profile;

class ChangePasswordDTO
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
        public readonly string $confirmPassword,
    ) {}
}
