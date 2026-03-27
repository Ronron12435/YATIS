# Coding Standards

## Laravel 12 Best Practices

Follow Laravel 12 conventions and best practices throughout the application.

## Architecture Layers

### Controllers (Thin Controllers)
- Controllers should only handle HTTP request/response logic
- Delegate all business logic to Service layer
- Keep controller methods focused and minimal
- Use dependency injection for services
- Convert request data to DTOs before passing to services
- Return Response objects from services

### Service Layer
- Encapsulate all business logic in services
- Accept DTOs as arguments instead of arrays
- Return Response objects instead of raw data
- Handle complex operations and workflows
- Manage transactions and data consistency
- Throw meaningful exceptions with context
- Keep services focused on single responsibility

### Repository Layer
- Repositories handle all database interactions
- Abstract database queries from services
- Implement consistent query patterns
- Use eager loading to prevent N+1 queries
- Return collections or single models

## Data Transfer Objects (DTOs)

**Location:** `app/DTOs/`

**Purpose:**
- Transfer data between layers
- Provide type safety and validation
- Document expected data structure
- Prevent passing raw arrays to services

**Example:**
```php
namespace App\DTOs;

class CreateJobPostingDTO
{
    public function __construct(
        public readonly int $employerId,
        public readonly string $title,
        public readonly string $jobType,
        public readonly string $location,
        public readonly ?int $businessId = null,
        public readonly ?string $salaryRange = null,
        public readonly ?string $requirements = null,
    ) {}
}
```

**Usage in Controllers:**
```php
public function store(CreateJobPostingRequest $request)
{
    $dto = new CreateJobPostingDTO(
        employerId: auth()->id(),
        title: $request->title,
        jobType: $request->job_type,
        location: $request->location,
        businessId: $request->business_id,
        salaryRange: $request->salary_range,
    );

    $response = $this->jobService->createJobPosting($dto);
    return response()->json($response->toArray(), $response->statusCode);
}
```

## Response Classes

**Location:** `app/Responses/`

**Purpose:**
- Standardize API responses
- Encapsulate response data and status codes
- Provide consistent response format across application

**Example:**
```php
namespace App\Responses;

class JobPostingResponse
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
```

**Usage in Services:**
```php
public function createJobPosting(CreateJobPostingDTO $dto): JobPostingResponse
{
    try {
        $job = $this->jobRepository->create([
            'employer_id' => $dto->employerId,
            'title'       => $dto->title,
            'job_type'    => $dto->jobType,
            'location'    => $dto->location,
            'status'      => 'open',
        ]);

        return new JobPostingResponse(
            success: true,
            data: $job,
            message: 'Job posting created successfully',
            statusCode: 201,
        );
    } catch (\Exception $e) {
        return new JobPostingResponse(
            success: false,
            data: null,
            message: 'Failed to create job posting',
            statusCode: 400,
            errors: ['error' => $e->getMessage()],
        );
    }
}
```

## Import Statements

- Always use the `use` keyword for importing classes
- Never use Fully Qualified Class Names (FQCN) in code
- Import all dependencies at the top of the file

Example:
```php
use App\Models\JobPosting;
use App\Services\JobService;
use App\Repositories\JobRepository;
use App\DTOs\CreateJobPostingDTO;
use App\Responses\JobPostingResponse;

// Good
$job = new JobPosting();

// Bad - Never do this
$job = new \App\Models\JobPosting();
```

## Model Best Practices

### Eager Loading
- Always prevent lazy loading in models
- Use eager loading with `with()` method
- Define relationships explicitly
- Load related data in repositories or queries

Example:
```php
// Good - Eager load relationships
$jobs = JobPosting::with(['employer', 'business', 'applications'])->get();

// Bad - Lazy loading
$jobs = JobPosting::all();
foreach ($jobs as $job) {
    $employer = $job->employer; // Lazy load - N+1 query
}
```

### Model Relationships
- Define all relationships in models
- Use proper relationship methods (hasMany, belongsTo, etc.)
- Include type hints in relationship methods

### Timestamp Conventions
- Most models use standard `created_at` / `updated_at`
- Models that only track creation use `const UPDATED_AT = null`
- JobApplication uses `const CREATED_AT = 'applied_at'` and `const UPDATED_AT = null`

## JavaScript Standards

- All JavaScript code must be in separate files
- Do not embed JavaScript in Blade templates
- Organize JavaScript files in `resources/js/` directory
- Use modules and imports for code organization
- Keep JavaScript focused and maintainable

Example:
```blade
<!-- Bad - Inline JavaScript -->
<script>
    document.getElementById('btn').addEventListener('click', function() {
        // code
    });
</script>

<!-- Good - Reference external file -->
<script src="{{ asset('js/jobs.js') }}"></script>
```
