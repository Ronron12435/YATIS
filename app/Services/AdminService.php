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
            'users_by_role'      => DB::table('users')->select('role', DB::raw('count(*) as count'))->groupBy('role')->get()->toArray(),
            'businesses_by_category' => DB::table('businesses')->select('category', DB::raw('count(*) as count'))->groupBy('category')->get()->toArray(),
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
                'total_business_users'      => DB::table('users')->where('role', 'business')->count(),
                'total_posts'               => DB::table('posts')->count(),
                'total_events'              => DB::table('events')->count(),
                'users_by_role'             => DB::table('users')->select('role', DB::raw('count(*) as count'))->groupBy('role')->get()->toArray(),
                'businesses_by_category'    => DB::table('businesses')->select('category', DB::raw('count(*) as count'))->groupBy('category')->get()->toArray(),
                'new_users_this_month'      => DB::table('users')->where('created_at', '>=', now()->startOfMonth())->count(),
                'new_business_users_this_month' => DB::table('users')->where('role', 'business')->where('created_at', '>=', now()->startOfMonth())->count(),
            ];

            return new ApiResponse(true, $stats, 'Success');
        } catch (\Exception $e) {
            \Log::error('AdminService getStatistics error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new ApiResponse(false, null, 'Error fetching statistics: ' . $e->getMessage(), 500);
        }
    }

    public function getBusinessUsers(): ApiResponse
    {
        try {
            $businessUsers = DB::table('users')
                ->where('role', 'business')
                ->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at')
                ->orderByDesc('created_at')
                ->get()
                ->toArray();

            return new ApiResponse(true, $businessUsers, 'Success');
        } catch (\Exception $e) {
            \Log::error('AdminService getBusinessUsers error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new ApiResponse(false, null, 'Error fetching business users: ' . $e->getMessage(), 500);
        }
    }
}
