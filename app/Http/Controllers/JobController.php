<?php

namespace App\Http\Controllers;

use App\DTOs\Job\ApplyJobDTO;
use App\DTOs\Job\CreateJobPostingDTO;
use App\Services\JobService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(private JobService $jobService) {}

    public function index(Request $request)
    {
        $response = $this->jobService->getAll(
            $request->input('search'),
            $request->input('location'),
            $request->input('job_type'),
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'requirements' => 'nullable|string',
            'job_type'     => 'required|string',
            'salary_range' => 'nullable|string',
            'location'     => 'required|string',
            'business_id'  => 'nullable|exists:businesses,id',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after:start_date',
        ]);

        $dto = new CreateJobPostingDTO(
            employerId: $request->user()->id,
            title: $validated['title'],
            description: $validated['description'],
            jobType: $validated['job_type'],
            location: $validated['location'],
            businessId: $validated['business_id'] ?? null,
            salaryRange: $validated['salary_range'] ?? null,
            requirements: $validated['requirements'] ?? null,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
        );

        $response = $this->jobService->create($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->jobService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'        => 'string|max:255',
            'description'  => 'string',
            'requirements' => 'nullable|string',
            'job_type'     => 'string',
            'salary_range' => 'nullable|string',
            'location'     => 'string',
            'status'       => 'in:open,closed',
        ]);

        $response = $this->jobService->update((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->jobService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function apply(Request $request, $id)
    {
        \Log::info('=== JOB APPLICATION DEBUG ===');
        \Log::info('Job ID:', ['id' => $id]);
        \Log::info('User ID:', ['user_id' => $request->user()->id]);
        \Log::info('Request all:', $request->all());
        \Log::info('Has file resume:', ['has_file' => $request->hasFile('resume')]);
        \Log::info('Files:', $request->files->all());

        $validated = $request->validate([
            'cover_letter' => 'nullable|string',
            'resume'       => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        \Log::info('Validated data:', $validated);

        $resumePath = null;
        if ($request->hasFile('resume')) {
            \Log::info('Storing resume file...');
            $resumePath = $request->file('resume')->store('resumes', 'public');
            \Log::info('Resume stored at:', ['path' => $resumePath]);
        }

        $dto = new ApplyJobDTO(
            jobId: (int) $id,
            userId: $request->user()->id,
            coverLetter: $validated['cover_letter'] ?? null,
            resumePath: $resumePath,
        );

        \Log::info('DTO created:', [
            'jobId' => $dto->jobId,
            'userId' => $dto->userId,
            'resumePath' => $dto->resumePath,
        ]);

        $response = $this->jobService->apply($dto);
        
        \Log::info('Service response:', $response->toArray());

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function myApplications(Request $request)
    {
        $response = $this->jobService->myApplications($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function applications(Request $request, $id)
    {
        $response = $this->jobService->getApplications((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function updateApplication(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,accepted,rejected',
            'interview_date' => 'nullable|date|after:now',
        ]);

        $response = $this->jobService->updateApplication((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function businessJobs($businessId)
    {
        $response = $this->jobService->getByBusiness((int) $businessId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function myJobs(Request $request)
    {
        $response = $this->jobService->myJobs($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function toggleStatus(Request $request, $id)
    {
        $response = $this->jobService->toggleStatus((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function setInterviewDate(Request $request, $id)
    {
        $validated = $request->validate([
            'interview_date' => 'required|date|after:now',
        ]);

        $response = $this->jobService->setInterviewDate((int) $id, $request->user()->id, $validated['interview_date']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function pendingApplicationsCount(Request $request)
    {
        $response = $this->jobService->pendingApplicationsCount($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
