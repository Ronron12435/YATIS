<?php

namespace App\Repositories;

use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JobRepository
{
    public function search(?string $search, ?string $location, ?string $jobType): LengthAwarePaginator
    {
        $query = DB::table('job_postings as jp')
            ->leftJoin('users as u', 'u.id', '=', 'jp.employer_id')
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->select('jp.*', 'u.username as employer_name', 'b.name as business_name', 'b.category as business_type')
            ->where('jp.status', '=', 'open')
            ->where(function ($q) {
                $q->whereNull('jp.deadline')->orWhere('jp.deadline', '>=', now());
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('jp.title', 'like', "%$search%")->orWhere('jp.description', 'like', "%$search%");
            });
        }

        if ($location) {
            $query->where('jp.location', 'like', "%$location%");
        }

        if ($jobType) {
            $query->where('jp.job_type', $jobType);
        }

        return $query->orderByDesc('jp.created_at')->paginate(15);
    }

    public function findById(int $id): ?object
    {
        $job = JobPosting::with(['employer', 'business'])
            ->where('status', 'open')
            ->find($id);

        if (!$job) {
            return null;
        }

        return (object) [
            'id' => $job->id,
            'employer_id' => $job->employer_id,
            'business_id' => $job->business_id,
            'title' => $job->title,
            'description' => $job->description,
            'requirements' => $job->requirements,
            'job_type' => $job->job_type,
            'salary_range' => $job->salary_range,
            'location' => $job->location,
            'status' => $job->status,
            'deadline' => $job->deadline,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
            'employer_name' => $job->employer?->username,
            'business_name' => $job->business?->name,
            'business_type' => $job->business?->category,
        ];
    }

    public function findRawById(int $id): ?object
    {
        return DB::table('job_postings')->where('id', $id)->first();
    }

    public function create(array $data): int
    {
        return JobPosting::create($data)->id;
    }

    public function update(int $id, array $data): void
    {
        JobPosting::find($id)?->update($data);
    }

    public function delete(int $id): void
    {
        JobPosting::destroy($id);
    }

    public function getByBusiness(int $businessId)
    {
        return JobPosting::where('business_id', $businessId)
            ->where('status', 'open')
            ->select('id', 'title', 'job_type', 'salary_range', 'location', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByEmployer(int $employerId)
    {
        return JobPosting::with('business')
            ->withCount(['applications' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->where('employer_id', $employerId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($job) {
                return (object) [
                    'id' => $job->id,
                    'title' => $job->title,
                    'location' => $job->location,
                    'created_at' => $job->created_at,
                    'job_type' => $job->job_type,
                    'salary_range' => $job->salary_range,
                    'status' => $job->status,
                    'business_name' => $job->business?->name,
                    'applications_count' => $job->applications_count,
                ];
            });
    }

    public function createApplication(array $data): JobApplication
    {
        return JobApplication::create($data);
    }

    public function findApplication(int $jobId, int $userId): ?JobApplication
    {
        return JobApplication::where('job_posting_id', $jobId)->where('user_id', $userId)->first();
    }

    public function findApplicationById(int $id): ?object
    {
        $app = JobApplication::with('jobPosting')
            ->find($id);

        if (!$app) {
            return null;
        }

        return (object) [
            'id' => $app->id,
            'employer_id' => $app->jobPosting?->employer_id,
        ];
    }

    public function getApplicationsByUser(int $userId)
    {
        return JobApplication::with(['jobPosting' => function ($query) {
            $query->with('business');
        }])
        ->where('user_id', $userId)
        ->orderByDesc('applied_at')
        ->get()
        ->map(function ($application) {
            return (object) [
                'id' => $application->id,
                'job_id' => $application->job_posting_id,
                'app_status' => $application->status,
                'applied_at' => $application->applied_at,
                'interview_date' => $application->interview_date,
                'job_title' => $application->jobPosting?->title ?? 'Deleted Job',
                'location' => $application->jobPosting?->location ?? 'N/A',
                'business_name' => $application->jobPosting?->business?->name ?? null,
            ];
        });
    }

    public function getApplicationsByJob(int $jobId)
    {
        return JobApplication::with('user')
            ->where('job_posting_id', $jobId)
            ->orderByDesc('applied_at')
            ->get()
            ->map(function ($app) {
                return (object) [
                    'id' => $app->id,
                    'app_status' => $app->status,
                    'applied_at' => $app->applied_at,
                    'interview_date' => $app->interview_date,
                    'resume' => $app->resume,
                    'cover_letter' => $app->cover_letter,
                    'username' => $app->user->username,
                    'first_name' => $app->user->first_name,
                    'last_name' => $app->user->last_name,
                    'email' => $app->user->email,
                ];
            });
    }

    public function updateApplication(int $id, array $data): void
    {
        JobApplication::find($id)?->update($data);
    }

    public function pendingApplicationsCount(int $employerId): int
    {
        return JobApplication::whereHas('jobPosting', function ($query) use ($employerId) {
            $query->where('employer_id', $employerId);
        })
        ->where('status', 'pending')
        ->count();
    }
}
