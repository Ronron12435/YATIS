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
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        
        // Check if employer_id exists, if not use business_id
        $employerColumn = in_array('employer_id', $columns) ? 'jp.employer_id' : 'jp.business_id';
        
        $query = DB::table('job_postings as jp')
            ->leftJoin('users as u', 'u.id', '=', $employerColumn)
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->select('jp.*', 'u.username as employer_name', 'b.name as business_name', 'b.category as business_type');
        
        // Only filter by status if the column exists
        if (in_array('status', $columns)) {
            $query->where('jp.status', '=', 'open');
        }
        
        // Only filter by is_active if the column exists
        if (in_array('is_active', $columns)) {
            $query->where('jp.is_active', true);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('jp.title', 'like', "%$search%")->orWhere('jp.description', 'like', "%$search%");
            });
        }

        if ($location) {
            $query->where('jp.location', 'like', "%$location%");
        }

        if ($jobType) {
            // Check if job_type column exists, otherwise use employment_type
            $jobTypeColumn = in_array('job_type', $columns) ? 'jp.job_type' : 'jp.employment_type';
            $query->where($jobTypeColumn, $jobType);
        }

        return $query->orderByDesc('jp.created_at')->paginate(15);
    }

    public function findById(int $id): ?object
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $employerColumn = in_array('employer_id', $columns) ? 'jp.employer_id' : 'jp.business_id';
        
        $query = DB::table('job_postings as jp')
            ->leftJoin('users as u', 'u.id', '=', $employerColumn)
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->select('jp.*', 'u.username as employer_name', 'b.name as business_name', 'b.category as business_type')
            ->where('jp.id', $id);
        
        // Only show open jobs to regular users
        if (in_array('status', $columns)) {
            $query->where('jp.status', '=', 'open');
        }
        
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
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $query = DB::table('job_postings')
            ->where('business_id', $businessId)
            ->orderByDesc('created_at');
        
        // Only show open jobs to regular users
        if (in_array('status', $columns)) {
            $query->where('status', '=', 'open');
            $query->select('id', 'title', 'job_type', 'salary_range', 'location', 'status', 'created_at');
        } else {
            $query->select('id', 'title', 'job_type', 'salary_range', 'location', 'created_at');
        }
        
        return $query->get();
    }

    public function getByEmployer(int $employerId)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $employerColumn = in_array('employer_id', $columns) ? 'jp.employer_id' : 'jp.business_id';
        
        $query = DB::table('job_postings as jp')
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id')
            ->where($employerColumn, $employerId)
            ->orderByDesc('jp.created_at');
        
        // Build select dynamically
        $selectColumns = ['jp.id', 'jp.title', 'jp.location', 'jp.created_at', 'b.name as business_name'];
        
        if (in_array('job_type', $columns)) {
            $selectColumns[] = 'jp.job_type';
        } elseif (in_array('employment_type', $columns)) {
            $selectColumns[] = 'jp.employment_type as job_type';
        }
        
        if (in_array('salary_range', $columns)) {
            $selectColumns[] = 'jp.salary_range';
        } elseif (in_array('salary_min', $columns) && in_array('salary_max', $columns)) {
            $selectColumns[] = DB::raw("CONCAT(jp.salary_min, ' - ', jp.salary_max) as salary_range");
        }
        
        if (in_array('status', $columns)) {
            $selectColumns[] = 'jp.status';
        }
        
        return $query->select($selectColumns)->get()
            ->map(function ($job) {
                $appColumns = DB::getSchemaBuilder()->getColumnListing('job_applications');
                $jobIdColumn = in_array('job_id', $appColumns) ? 'job_id' : 'job_posting_id';
                $job->applications_count = DB::table('job_applications')->where($jobIdColumn, $job->id)->count();
                return $job;
            });
    }

    public function createApplication(array $data): JobApplication
    {
        return JobApplication::create($data);
    }

    public function findApplication(int $jobId, int $userId): ?JobApplication
    {
        return JobApplication::where('job_id', $jobId)->where('user_id', $userId)->first();
    }

    public function findApplicationById(int $id): ?object
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $employerColumn = in_array('employer_id', $columns) ? 'jp.employer_id' : 'jp.business_id';
        
        return DB::table('job_applications as ja')
            ->join('job_postings as jp', 'jp.id', '=', 'ja.job_id')
            ->select('ja.id', $employerColumn . ' as employer_id')
            ->where('ja.id', $id)
            ->first();
    }

    public function getApplicationsByUser(int $userId)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_applications');
        $jobColumns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        
        // Check if job_id exists, if not use job_posting_id
        $jobIdColumn = in_array('job_id', $columns) ? 'ja.job_id' : 'ja.job_posting_id';
        
        // Check if applied_at exists, if not use created_at
        $appliedAtColumn = in_array('applied_at', $columns) ? 'ja.applied_at' : 'ja.created_at';
        
        // Check if interview_date exists
        $hasInterviewDate = in_array('interview_date', $columns);
        $hasResumePath = in_array('resume_path', $columns);
        
        $query = DB::table('job_applications as ja')
            ->join('job_postings as jp', 'jp.id', '=', $jobIdColumn)
            ->leftJoin('businesses as b', 'b.id', '=', 'jp.business_id');
        
        // Build select dynamically based on available columns
        $selectColumns = ['ja.id', $jobIdColumn . ' as job_id', 'ja.status as app_status', $appliedAtColumn . ' as applied_at', 'jp.title as job_title', 'jp.location', 'b.name as business_name'];
        
        if ($hasInterviewDate) {
            $selectColumns[] = 'ja.interview_date';
        }
        if ($hasResumePath) {
            $selectColumns[] = 'ja.resume_path';
        }
        
        return $query->select($selectColumns)
            ->where('ja.user_id', $userId)
            ->orderByDesc($appliedAtColumn)
            ->get();
    }

    public function getApplicationsByJob(int $jobId)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_applications');
        
        // Check if job_id exists, if not use job_posting_id
        $jobIdColumn = in_array('job_id', $columns) ? 'ja.job_id' : 'ja.job_posting_id';
        
        // Check if applied_at exists, if not use created_at
        $appliedAtColumn = in_array('applied_at', $columns) ? 'ja.applied_at' : 'ja.created_at';
        $hasInterviewDate = in_array('interview_date', $columns);
        $hasResumePath = in_array('resume_path', $columns);
        $hasCoverLetter = in_array('cover_letter', $columns);
        
        $selectColumns = ['ja.id', 'ja.status as app_status', $appliedAtColumn . ' as applied_at', 'u.username', 'u.first_name', 'u.last_name', 'u.email'];
        
        if ($hasInterviewDate) {
            $selectColumns[] = 'ja.interview_date';
        }
        if ($hasResumePath) {
            $selectColumns[] = 'ja.resume_path';
        }
        if ($hasCoverLetter) {
            $selectColumns[] = 'ja.cover_letter';
        }
        
        return DB::table('job_applications as ja')
            ->join('users as u', 'u.id', '=', 'ja.user_id')
            ->select($selectColumns)
            ->where($jobIdColumn, $jobId)
            ->orderByDesc($appliedAtColumn)
            ->get();
    }

    public function updateApplication(int $id, array $data): void
    {
        DB::table('job_applications')->where('id', $id)->update($data);
    }

    public function pendingApplicationsCount(int $employerId): int
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $employerColumn = in_array('employer_id', $columns) ? 'jp.employer_id' : 'jp.business_id';
        
        return DB::table('job_applications as ja')
            ->join('job_postings as jp', 'jp.id', '=', 'ja.job_id')
            ->where($employerColumn, $employerId)
            ->where('ja.status', 'pending')
            ->count();
    }
}
