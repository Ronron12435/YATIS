<?php

namespace App\Responses;

class LocationResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data,
        public readonly string $message,
        public readonly int $statusCode = 200,
        public readonly ?array $errors = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
