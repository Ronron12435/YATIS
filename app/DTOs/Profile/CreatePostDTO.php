<?php

namespace App\DTOs\Profile;

class CreatePostDTO
{
    public function __construct(
        public readonly string $content,
        public readonly string $privacy = 'public',
        public readonly ?string $image = null,
    ) {}
}
