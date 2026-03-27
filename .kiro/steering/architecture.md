# Application Architecture

## Overview

YATIS follows a layered architecture pattern with clear separation of concerns. The architecture is built on Laravel 12 best practices with three main layers: Controllers, Services, and Repositories.

## Architecture Layers

```
┌─────────────────────────────────────────┐
│         HTTP Requests/Responses         │
├─────────────────────────────────────────┤
│      Controllers (Thin Layer)           │
│  - Handle HTTP request/response         │
│  - Validate input via Form Requests     │
│  - Delegate to Services                 │
├─────────────────────────────────────────┤
│      Service Layer (Business Logic)     │
│  - Encapsulate business logic           │
│  - Manage transactions                  │
│  - Orchestrate repositories             │
│  - Handle complex workflows             │
├─────────────────────────────────────────┤
│    Repository Layer (Data Access)       │
│  - Abstract database queries            │
│  - Implement eager loading              │
│  - Return models/collections            │
├─────────────────────────────────────────┤
│      Models (Eloquent ORM)              │
│  - Define relationships                 │
│  - Database schema representation       │
├─────────────────────────────────────────┤
│         Database                        │
└─────────────────────────────────────────┘
```

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php           # Registration, login, logout
│   │   ├── UserController.php           # User CRUD
│   │   ├── ProfileController.php        # Profile management
│   │   ├── BusinessController.php       # Business + menu/products/services/tables
│   │   ├── JobController.php            # Job postings + applications
│   │   ├── PostController.php           # Social posts
│   │   ├── FriendshipController.php     # Friend system
│   │   ├── MessageController.php        # Private + group messaging
│   │   ├── GroupController.php          # Group management
│   │   ├── DestinationController.php    # Tourist destinations + reviews
│   │   ├── EventController.php          # Events + tasks + achievements
│   │   ├── TableController.php          # Restaurant table reservations
│   │   ├── SearchController.php         # Search across all entities
│   │   ├── AdminController.php          # Admin dashboard
│   │   └── DashboardController.php      # Main dashboard
│   ├── Middleware/
│   │   ├── AdminMiddleware.php          # Restricts to admin role
│   │   └── BusinessOwnerMiddleware.php  # Allows business/employer/admin
│   └── Requests/                        # Form request validation
├── Models/                              # Eloquent models
├── Services/                            # Business logic services
├── Repositories/                        # Data access layer
├── DTOs/                                # Data Transfer Objects
├── Responses/                           # Response classes
└── Providers/                           # Service providers

resources/
├── views/                               # Blade templates
└── js/                                  # Separate JavaScript files

database/
├── migrations/                          # Schema migrations
└── seeders/                             # Database seeders
```

## Layer Responsibilities

### Controllers Layer

**Location:** `app/Http/Controllers/`

**Responsibilities:**
- Receive HTTP requests
- Validate input using Form Requests
- Call appropriate services
- Return JSON responses
- Handle HTTP status codes

**Example Structure:**
```php
namespace App\Http\Controllers;

use App\Services\JobService;
use App\Http\Requests\CreateJobPostingRequest;
use App\DTOs\CreateJobPostingDTO;

class JobController extends Controller
{
    public function __construct(private JobService $jobService) {}

    public function store(CreateJobPostingRequest $request)
    {
        $dto = new CreateJobPostingDTO(
            employerId: auth()->id(),
            title: $request->title,
            jobType: $request->job_type,
            location: $request->location,
        );

        $response = $this->jobService->createJobPosting($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
```

### Service Layer

**Location:** `app/Services/`

**Responsibilities:**
- Implement business logic
- Manage transactions
- Orchestrate repositories
- Throw meaningful exceptions
- Handle complex workflows

**Example Structure:**
```php
namespace App\Services;

use App\Repositories\JobRepository;
use App\Repositories\BusinessRepository;
use App\DTOs\CreateJobPostingDTO;
use App\Responses\JobPostingResponse;

class JobService
{
    public function __construct(
        private JobRepository $jobRepository,
        private BusinessRepository $businessRepository
    ) {}

    public function createJobPosting(CreateJobPostingDTO $dto): JobPostingResponse
    {
        if ($dto->businessId) {
            $business = $this->businessRepository->findById($dto->businessId);
            if (!$business) {
                throw new \Exception('Business not found');
            }
        }

        $job = $this->jobRepository->create([
            'employer_id' => $dto->employerId,
            'title'       => $dto->title,
            'job_type'    => $dto->jobType,
            'location'    => $dto->location,
            'status'      => 'open',
        ]);

        return new JobPostingResponse(success: true, data: $job, message: 'Job posting created', statusCode: 201);
    }
}
```

### Repository Layer

**Location:** `app/Repositories/`

**Responsibilities:**
- Abstract database queries
- Implement eager loading
- Return models or collections
- Provide consistent query patterns
- Handle query optimization

**Example Structure:**
```php
namespace App\Repositories;

use App\Models\JobPosting;

class JobRepository
{
    public function create(array $data): JobPosting
    {
        return JobPosting::create($data);
    }

    public function findById(int $id): ?JobPosting
    {
        return JobPosting::with(['employer', 'business', 'applications'])->find($id);
    }

    public function getByEmployer(int $employerId)
    {
        return JobPosting::with(['business', 'applications'])
            ->where('employer_id', $employerId)
            ->orderByDesc('created_at')
            ->get();
    }
}
```

### Models Layer

**Location:** `app/Models/`

**Responsibilities:**
- Define database relationships
- Represent database schema
- Include type hints and docblocks
- Use accessors/mutators for data transformation

**Example Structure:**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    protected $fillable = ['employer_id', 'business_id', 'title', 'job_type', 'location', 'status'];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }
}
```

## Data Flow

### Request Flow

1. HTTP Request → Routes to appropriate controller
2. Controller → Validates input via Form Request
3. Service → Executes business logic
4. Repository → Queries database with eager loading
5. Model → Returns data with relationships
6. Service → Processes and returns result
7. Controller → Formats and returns JSON response

### Example: Create Job Posting

```
POST /api/jobs
    ↓
JobController::store()
    ↓
CreateJobPostingRequest (validation)
    ↓
JobService::createJobPosting()
    ↓
BusinessRepository::findById()
    ↓
JobRepository::create()
    ↓
JobPosting Model (with relationships)
    ↓
JSON Response
```

## Role-Based Access

Middleware controls access by role:

- `auth:sanctum` - All authenticated routes
- `AdminMiddleware` - Admin-only routes (`/api/admin/*`)
- `BusinessOwnerMiddleware` - Business, employer, or admin roles

## Frontend Architecture

- JavaScript Files - Separate files in `resources/js/`
- Blade Templates - In `resources/views/` organized by feature
- No Inline JavaScript - All JavaScript in separate files
- Module Organization - Use ES6 modules for code organization

## Database Layer

- Migrations - All schema changes via migrations
- Relationships - Defined in models
- Eager Loading - Always use `with()` to prevent N+1 queries
- Indexes - On frequently queried columns (user_id, employer_id, status)
- Transactions - For multi-step operations in services
- Timestamps - Some models use `const UPDATED_AT = null` (messages, reviews, etc.)

## Key Principles

1. Separation of Concerns - Each layer has specific responsibility
2. Thin Controllers - Controllers only handle HTTP logic
3. Business Logic in Services - All logic in service layer
4. Data Access in Repositories - Repositories handle queries
5. Eager Loading - Always load relationships to prevent N+1
6. Dependency Injection - Use constructor injection
7. Use Keyword - Always use `use` for imports, never FQCN
8. Separate JavaScript - All JS in separate files
