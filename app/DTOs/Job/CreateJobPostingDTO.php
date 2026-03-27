<?php

namespace App\DTOs\Job;

class CreateJobPostingDTO
{
    public function __construct(
        public readonly int $employerId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $jobType,
        public readonly string $location,
        public readonly ?int $businessId = null,
        public readonly ?string $salaryRange = null,
        public readonly ?string $requirements = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {}
}
