<?php

namespace App\DTOs\Message;

class SendGroupMessageDTO
{
    public function __construct(
        public readonly int $groupId,
        public readonly int $senderId,
        public readonly string $content,
    ) {}
}
