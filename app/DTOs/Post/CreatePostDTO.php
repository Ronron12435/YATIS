<?php

namespace App\DTOs\Post;

class CreatePostDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $content,
        public readonly ?string $image = null,
    ) {}
}
