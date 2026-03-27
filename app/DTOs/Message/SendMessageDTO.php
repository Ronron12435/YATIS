<?php

namespace App\DTOs\Message;

class SendMessageDTO
{
    public function __construct(
        public readonly int $senderId,
        public readonly int $recipientId,
        public readonly string $content,
    ) {}
}
