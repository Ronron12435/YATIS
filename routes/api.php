<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    ProfileController,
    BusinessController,
    PostController,
    DestinationController,
    EventController,
    FriendshipController,
    GroupController,
    MessageController,
    JobController,
    AdminController,
    TableController,
    SearchController
};

// Public API routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Serve uploaded files
Route::get('/uploads/{type}/{filename}', function ($type, $filename) {
    $path = base_path("uploads/{$type}/{$filename}");
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path);
})->where('filename', '.*');

// Public endpoints (no auth required)
Route::get('/businesses', [BusinessController::class, 'index']);
Route::get('/businesses/{id}', [BusinessController::class, 'show']);
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{id}', [DestinationController::class, 'show']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show']);
Route::get('/search', [SearchController::class, 'index']);

// Debug endpoint - check user roles
Route::get('/debug/user-roles', [UserController::class, 'checkRoles']);
Route::post('/debug/fix-user-roles', [UserController::class, 'fixUserRoles']);

// Protected API routes - use minimal middleware for session support
Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\AuthenticateWithSessionOrSanctum::class
])->group(function () {
// Auth
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);
    
    // Users
    Route::apiResource('users', UserController::class);
    
    // User Location Routes
    Route::post('/user/location', [UserController::class, 'updateLocation']);
    Route::get('/user/location', [UserController::class, 'getLocation']);
    Route::get('/user/businesses', [UserController::class, 'businesses']);
    
    // Profile Routes
    Route::get('/profile/current', [ProfileController::class, 'current']);
    Route::get('/profile/current/posts', [ProfileController::class, 'getPosts']);
    Route::get('/profile/current/visitors', [ProfileController::class, 'getVisitors']);
    Route::get('/profile/current/achievements', [ProfileController::class, 'getAchievements']);
    Route::get('/profile/{id}', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'changePassword']);
    
    // Post Routes
    Route::post('/profile/posts', [ProfileController::class, 'createPost']);
    Route::delete('/profile/posts/{id}', [ProfileController::class, 'deletePost']);
    Route::get('/profile/{id}/posts', [ProfileController::class, 'getPosts']);
    
    // Visitor Routes
    Route::get('/profile/{id}/visitors', [ProfileController::class, 'getVisitors']);
    Route::post('/profile/{id}/visit', [ProfileController::class, 'recordVisit']);
    
    // Photo Routes
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::post('/profile/cover', [ProfileController::class, 'uploadCover']);
    Route::delete('/profile/cover', [ProfileController::class, 'deleteCover']);
    
    // Achievements Routes
    Route::get('/profile/{id}/achievements', [ProfileController::class, 'getAchievements']);
    Route::get('/profile/{id}/businesses', [ProfileController::class, 'businesses']);
    
    // Businesses
    Route::apiResource('businesses', BusinessController::class);
    Route::get('/businesses-map', [BusinessController::class, 'map']);
    Route::get('/businesses/{id}/is-open', [BusinessController::class, 'isOpen']);
    Route::get('/businesses/{id}/hours', [BusinessController::class, 'hours']);
    
    // Posts
    Route::apiResource('posts', PostController::class);
    Route::get('/posts/user/{userId}', [PostController::class, 'userPosts']);
    
    // Friendships
    Route::get('/friends/requests', [FriendshipController::class, 'requests']);
    Route::get('/friends-list', [FriendshipController::class, 'index']);
    Route::post('/friends/{userId}/add', [FriendshipController::class, 'add']);
    Route::post('/friends/{userId}/accept', [FriendshipController::class, 'accept']);
    Route::post('/friends/{userId}/reject', [FriendshipController::class, 'reject']);
    Route::post('/friends/{userId}/block', [FriendshipController::class, 'block']);
    Route::get('/friends/{userId}/status', [FriendshipController::class, 'status']);
    Route::delete('/friends/{userId}', [FriendshipController::class, 'remove']);
    Route::apiResource('friends', FriendshipController::class);
    
    // Messages
    Route::get('/messages/unread/count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/{userId}', [MessageController::class, 'show']);
    Route::apiResource('messages', MessageController::class);
    
    // Users
    Route::get('/people-map', [UserController::class, 'peopleMap']);
    Route::post('/profile/update-location', [ProfileController::class, 'updateLocation']);
    
    // Groups
    Route::get('/groups/user/my-groups', [GroupController::class, 'userGroups']);
    Route::get('/groups/public', [GroupController::class, 'publicGroups']);
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{id}/members', [GroupController::class, 'addMember']);
    Route::delete('/groups/{id}/members/{userId}', [GroupController::class, 'removeMember']);
    
    // Destinations
    Route::apiResource('destinations', DestinationController::class);
    Route::post('/destinations/{id}/reviews', [DestinationController::class, 'addReview']);
    Route::get('/destinations/{id}/reviews', [DestinationController::class, 'reviews']);
    Route::get('/destinations-dashboard', [DestinationController::class, 'dashboardData']);
    
    // Events
    Route::apiResource('events', EventController::class);
    
    // Jobs
    Route::get('/jobs/pending-applications-count', [JobController::class, 'pendingApplicationsCount']);
    Route::get('/jobs/applications/my-applications', [JobController::class, 'myApplications']);
    Route::get('/jobs/business/{businessId}', [JobController::class, 'businessJobs']);
    Route::get('/my-jobs', [JobController::class, 'myJobs']);
    Route::post('/jobs/{id}/apply', [JobController::class, 'apply']);
    Route::post('/jobs/{id}/toggle-status', [JobController::class, 'toggleStatus']);
    Route::get('/jobs/{id}/applications', [JobController::class, 'applications']);
    Route::put('/jobs/applications/{id}', [JobController::class, 'updateApplication']);
    Route::post('/jobs/applications/{id}/status', [JobController::class, 'updateApplication']);
    Route::post('/jobs/applications/{id}/interview', [JobController::class, 'setInterviewDate']);
    Route::post('/jobs/applications/{id}/set-interview-date', [JobController::class, 'setInterviewDate']);
    Route::apiResource('jobs', JobController::class);
    
    // Tables
    Route::apiResource('tables', TableController::class);
    Route::post('/tables/{id}/reserve', [TableController::class, 'reserve']);
    Route::post('/tables/{id}/release', [TableController::class, 'release']);
    Route::get('/tables/available', [TableController::class, 'available']);
    Route::get('/businesses/{businessId}/tables', [TableController::class, 'getByBusiness']);
    
    // Search
    Route::get('/search/users', [SearchController::class, 'users']);
    Route::get('/search/businesses', [SearchController::class, 'businesses']);
    Route::get('/search/destinations', [SearchController::class, 'destinations']);
    Route::get('/search/posts', [SearchController::class, 'posts']);
    Route::get('/search/advanced', [SearchController::class, 'advanced']);
    
    // Business menu items, products, services
    Route::post('/businesses/{businessId}/menu-items', [BusinessController::class, 'addMenuItem']);
    Route::get('/businesses/{businessId}/menu-items', [BusinessController::class, 'getMenuItems']);
    Route::delete('/menu-items/{itemId}', [BusinessController::class, 'deleteMenuItem']);
    
    Route::post('/businesses/{businessId}/products', [BusinessController::class, 'addProduct']);
    Route::get('/businesses/{businessId}/products', [BusinessController::class, 'getProducts']);
    Route::delete('/products/{productId}', [BusinessController::class, 'deleteProduct']);
    
    Route::post('/businesses/{businessId}/services', [BusinessController::class, 'addService']);
    Route::get('/businesses/{businessId}/services', [BusinessController::class, 'getServices']);
    Route::delete('/services/{serviceId}', [BusinessController::class, 'deleteService']);
    
    Route::get('/my-businesses', [BusinessController::class, 'myBusinesses']);
    Route::post('/businesses/{businessId}/generate-tables', [BusinessController::class, 'generateTables']);
    
    // Event tasks and gamification
    Route::post('/events/{eventId}/tasks', [EventController::class, 'createTask']);
    Route::get('/events/{eventId}/tasks', [EventController::class, 'eventTasks']);
    Route::post('/events/tasks/complete', [EventController::class, 'completeTask']);
    Route::delete('/events/tasks/{taskId}', [EventController::class, 'deleteTask']);
    Route::get('/achievements', [EventController::class, 'userAchievements']);
    Route::get('/leaderboard', [EventController::class, 'leaderboard']);
    
    // Daily steps tracking
    Route::get('/steps/today', [EventController::class, 'getTodaySteps']);
    Route::post('/steps/record', [EventController::class, 'recordSteps']);
    Route::post('/steps/increment', [EventController::class, 'incrementSteps']);
    
    // Group messaging
    Route::post('/groups/{id}/messages', [MessageController::class, 'sendGroupMessage']);
    Route::get('/groups/{id}/messages', [MessageController::class, 'getGroupMessages']);
    Route::post('/messages/{messageId}/mark-as-read', [MessageController::class, 'markAsRead']);
    Route::post('/group-messages/{messageId}/mark-as-read', [MessageController::class, 'markGroupAsRead']);
    Route::get('/group-unread-counts', [MessageController::class, 'getGroupUnreadCounts']);
    
        // Admin features
        Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->prefix('admin')->group(function () {
            Route::get('/businesses', [AdminController::class, 'businesses']);
            Route::get('/business-users', [AdminController::class, 'businessUsers']);
            Route::get('/events', [AdminController::class, 'events']);
            Route::delete('/events/{id}', [AdminController::class, 'deleteEvent']);
            Route::get('/statistics', [AdminController::class, 'statistics']);
            Route::post('/create-business-account', [AdminController::class, 'createBusinessAccount']);
        });
    });
