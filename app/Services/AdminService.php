<?php

namespace App\Services;

use App\Repositories\BusinessRepository;
use App\Repositories\EventRepository;
use App\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function __construct(
        private BusinessRepository $businessRepository,
        private EventRepository $eventRepository,
    ) {}

    public function getDashboard(): ApiResponse
    {
        $stats = [
            'total_users'        => DB::table('users')->count(),
            'total_businesses'   => DB::table('businesses')->count(),
            'total_posts'        => DB::table('posts')->count(),
            'total_events'       => DB::table('events')->count(),
            'users_by_role'      => DB::table('users')->select('role', DB::raw('count(*) as count'))->groupBy('role')->get(),
            'businesses_by_type' => DB::table('businesses')->select('business_type', DB::raw('count(*) as count'))->groupBy('business_type')->get(),
        ];

        return new ApiResponse(true, $stats, 'Success');
    }

    public function getBusinesses(?string $search, ?string $type): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->search($search, $type, null), 'Success');
    }

    public function getEvents(): ApiResponse
    {
        return new ApiResponse(true, $this->eventRepository->search(null), 'Success');
    }

    public function deleteEvent(int $id): ApiResponse
    {
        $event = $this->eventRepository->findById($id);

        if (!$event) {
            return new ApiResponse(false, null, 'Event not found', 404);
        }

        $this->eventRepository->delete($event);

        return new ApiResponse(true, null, 'Event deleted');
    }

    public function getStatistics(): ApiResponse
    {
        try {
            $stats = [
                'total_users'               => DB::table('users')->count(),
                'total_businesses'          => DB::table('businesses')->count(),
                'total_posts'               => DB::table('posts')->count(),
                'total_events'              => DB::table('events')->count(),
                'users_by_role'             => DB::table('users')->select('role', DB::raw('count(*) as count'))->groupBy('role')->get(),
                'businesses_by_type'        => DB::table('businesses')->select('business_type', DB::raw('count(*) as count'))->groupBy('business_type')->get(),
                'new_users_this_month'      => DB::table('users')->where('created_at', '>=', now()->startOfMonth())->count(),
                'new_businesses_this_month' => DB::table('businesses')->where('created_at', '>=', now()->startOfMonth())->count(),
            ];

            return new ApiResponse(true, $stats, 'Success');
        } catch (\Exception $e) {
            return new ApiResponse(true, [
                'total_users' => 0,
                'total_businesses' => 0,
                'total_posts' => 0,
                'total_events' => 0,
                'users_by_role' => [],
                'businesses_by_type' => [],
                'new_users_this_month' => 0,
                'new_businesses_this_month' => 0,
            ], 'Success');
        }
    }
}
