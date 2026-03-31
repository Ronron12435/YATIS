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
        $query = DB::table('job_postings as jp')
            ->leftJoin('users as u', 'u.id', '=', 'jp.employer_id')
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->select('jp.*', 'u.username as employer_name', 'b.name as business_name', 'b.category as business_type')
            ->where('jp.id', $id)
            ->where('jp.status', '=', 'open');
        
        return $query->first();
    }

    public function findRawById(int $id): ?object
    {
        return DB::table('job_postings')->where('id', $id)->first();
    }

    public function create(array $data): int
    {
        return DB::table('job_postings')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        DB::table('job_postings')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        DB::table('job_postings')->where('id', $id)->delete();
    }

    public function getByBusiness(int $businessId)
    {
        return DB::table('job_postings')
            ->where('business_id', $businessId)
            ->where('status', '=', 'open')
            ->select('id', 'title', 'job_type', 'salary_range', 'location', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByEmployer(int $employerId)
    {
        return DB::table('job_postings as jp')
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->where('jp.employer_id', $employerId)
            ->select([
                'jp.id', 'jp.title', 'jp.location', 'jp.created_at', 'jp.job_type',
                'jp.salary_range', 'jp.status', 'b.name as business_name'
            ])
            ->orderByDesc('jp.created_at')
            ->get()
            ->map(function ($job) {
                $job->applications_count = DB::table('job_applications')
                    ->where('job_posting_id', $job->id)
                    ->where('status', 'pending')
                    ->count();
                return $job;
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
        return DB::table('job_applications as ja')
            ->join('job_postings as jp', 'jp.id', '=', 'ja.job_posting_id')
            ->select('ja.id', 'jp.employer_id')
            ->where('ja.id', $id)
            ->first();
    }

    public function getApplicationsByUser(int $userId)
    {
        return DB::table('job_applications as ja')
            ->leftJoin('job_postings as jp', 'jp.id', '=', 'ja.job_posting_id')
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->select([
                'ja.id',
                'ja.job_posting_id as job_id',
                'ja.status as app_status',
                'ja.applied_at as applied_at',
                'ja.interview_date',
                'jp.title as job_title',
                'jp.location',
                'b.name as business_name'
            ])
            ->where('ja.user_id', $userId)
            ->orderByDesc('ja.applied_at')
            ->get();
    }

    public function getApplicationsByJob(int $jobId)
    {
        $selectColumns = [
            'ja.id',
            'ja.status as app_status',
            'ja.applied_at as applied_at',
            'ja.interview_date',
            'ja.resume',
            'ja.cover_letter',
            'u.username',
            'u.first_name',
            'u.last_name',
            'u.email'
        ];
        
        return DB::table('job_applications as ja')
            ->join('users as u', 'u.id', '=', 'ja.user_id')
            ->select($selectColumns)
            ->where('ja.job_posting_id', $jobId)
            ->orderByDesc('ja.applied_at')
            ->get();
    }

    public function updateApplication(int $id, array $data): void
    {
        DB::table('job_applications')->where('id', $id)->update($data);
    }

    public function pendingApplicationsCount(int $employerId): int
    {
        return DB::table('job_applications as ja')
            ->join('job_postings as jp', 'jp.id', '=', 'ja.job_posting_id')
            ->where('jp.employer_id', $employerId)
            ->where('ja.status', 'pending')
            ->count();
    }
}
