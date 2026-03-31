<?php

namespace App\Services;

use App\DTOs\Job\ApplyJobDTO;
use App\DTOs\Job\CreateJobPostingDTO;
use App\Repositories\JobRepository;
use App\Responses\ApiResponse;

class JobService
{
    public function __construct(private JobRepository $jobRepository) {}

    public function getAll(?string $search, ?string $location, ?string $jobType): ApiResponse
    {
        return new ApiResponse(true, $this->jobRepository->search($search, $location, $jobType), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $job = $this->jobRepository->findById($id);

        if (!$job) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        return new ApiResponse(true, $job, 'Success');
    }

    public function create(CreateJobPostingDTO $dto): ApiResponse
    {
        $id = $this->jobRepository->create([
            'employer_id'  => $dto->employerId,
            'business_id'  => $dto->businessId,
            'title'        => $dto->title,
            'position'     => $dto->title,
            'description'  => $dto->description,
            'requirements' => $dto->requirements,
            'job_type'     => $dto->jobType,
            'salary_range' => $dto->salaryRange,
            'location'     => $dto->location,
            'status'       => 'open',
            'deadline'     => $dto->endDate ?? now()->addDays(30)->toDateString(),
            'created_at'   => now(),
        ]);

        return new ApiResponse(true, ['id' => $id], 'Job posting created', 201);
    }

    public function update(int $id, int $authId, array $data): ApiResponse
    {
        $job = $this->jobRepository->findRawById($id);

        if (!$job) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        if ($authId !== $job->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->jobRepository->update($id, $data);

        return new ApiResponse(true, null, 'Job posting updated');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $job = $this->jobRepository->findRawById($id);

        if (!$job) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        if ($authId !== $job->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->jobRepository->delete($id);

        return new ApiResponse(true, null, 'Job posting deleted');
    }

    public function apply(ApplyJobDTO $dto): ApiResponse
    {
        if (!$this->jobRepository->findRawById($dto->jobId)) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        if ($this->jobRepository->findApplication($dto->jobId, $dto->userId)) {
            return new ApiResponse(false, null, 'Already applied to this job', 400);
        }

        $application = $this->jobRepository->createApplication([
            'job_posting_id' => $dto->jobId,
            'user_id'        => $dto->userId,
            'cover_letter'   => $dto->coverLetter,
            'resume'         => $dto->resumePath,
            'status'         => 'pending',
        ]);

        return new ApiResponse(true, $application, 'Application submitted', 201);
    }

    public function myApplications(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->jobRepository->getApplicationsByUser($userId), 'Success');
    }

    public function getApplications(int $jobId, int $authId): ApiResponse
    {
        $job = $this->jobRepository->findRawById($jobId);

        if (!$job) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        if ($authId !== $job->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->jobRepository->getApplicationsByJob($jobId), 'Success');
    }

    public function updateApplication(int $appId, int $authId, array $data): ApiResponse
    {
        $app = $this->jobRepository->findApplicationById($appId);

        if (!$app) {
            return new ApiResponse(false, null, 'Application not found', 404);
        }

        if ($authId !== $app->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $updateData = ['status' => $data['status']];
        if (isset($data['interview_date'])) {
            $updateData['interview_date'] = $data['interview_date'];
        }

        $this->jobRepository->updateApplication($appId, $updateData);

        return new ApiResponse(true, null, 'Application status updated');
    }

    public function getByBusiness(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->jobRepository->getByBusiness($businessId), 'Success');
    }

    public function myJobs(int $employerId): ApiResponse
    {
        return new ApiResponse(true, $this->jobRepository->getByEmployer($employerId), 'Success');
    }

    public function toggleStatus(int $id, int $authId): ApiResponse
    {
        $job = $this->jobRepository->findRawById($id);

        if (!$job) {
            return new ApiResponse(false, null, 'Job not found', 404);
        }

        if ($authId !== $job->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $newStatus = $job->status === 'open' ? 'closed' : 'open';
        $this->jobRepository->update($id, ['status' => $newStatus]);

        return new ApiResponse(true, ['status' => $newStatus], 'Job status updated');
    }

    public function setInterviewDate(int $appId, int $authId, string $interviewDate): ApiResponse
    {
        $app = $this->jobRepository->findApplicationById($appId);

        if (!$app) {
            return new ApiResponse(false, null, 'Application not found', 404);
        }

        if ($authId !== $app->employer_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->jobRepository->updateApplication($appId, [
            'interview_date' => $interviewDate,
            'status'         => 'reviewed',
        ]);

        return new ApiResponse(true, null, 'Interview date set');
    }

    public function pendingApplicationsCount(int $employerId): ApiResponse
    {
        return new ApiResponse(true, ['count' => $this->jobRepository->pendingApplicationsCount($employerId)], 'Success');
    }
}
