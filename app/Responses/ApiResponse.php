<?php

namespace App\Responses;

class ApiResponse
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
            'message' => $this->message,
            'data'    => $this->data,
            'errors'  => $this->errors,
        ];
    }
}
