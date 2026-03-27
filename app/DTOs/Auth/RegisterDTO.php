<?php

namespace App\DTOs\Auth;

class RegisterDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly string $role = 'user',
    ) {}
}
