<?php

namespace App\DTOs\Job;

class ApplyJobDTO
{
    public function __construct(
        public readonly int $jobId,
        public readonly int $userId,
        public readonly ?string $coverLetter = null,
        public readonly ?string $resumePath = null,
    ) {}
}
