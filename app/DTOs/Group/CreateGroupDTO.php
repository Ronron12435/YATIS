<?php

namespace App\DTOs\Group;

class CreateGroupDTO
{
    public function __construct(
        public readonly int $creatorId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $isPrivate = false,
        public readonly ?string $avatar = null,
    ) {}
}
