<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check column existence for flexible schema support
        $jobColumns = DB::getSchemaBuilder()->getColumnListing('job_postings');
        $businessColumns = DB::getSchemaBuilder()->getColumnListing('businesses');
        
        // Count jobs based on available columns
        $jobsQuery = DB::table('job_postings');
        if (in_array('status', $jobColumns)) {
            $jobsCount = $jobsQuery->where('status', 'open')->count();
        } else {
            $jobsCount = $jobsQuery->where('is_active', true)->count();
        }

        // Count businesses by type if column exists
        $foodCount = 0;
        $goodsCount = 0;
        $servicesCount = 0;
        
        if (in_array('category', $businessColumns)) {
            $foodCount = DB::table('businesses')->where('category', 'food')->count();
            $goodsCount = DB::table('businesses')->where('category', 'goods')->count();
            $servicesCount = DB::table('businesses')->where('category', 'services')->count();
        }

        // Get pending applications count for business users
        $pendingApplicationsCount = 0;
        if ($user->role === 'business' || $user->role === 'employer') {
            $pendingApplicationsCount = DB::table('job_applications as ja')
                ->join('job_postings as jp', 'jp.id', '=', 'ja.job_posting_id')
                ->where('jp.employer_id', $user->id)
                ->where('ja.status', 'pending')
                ->count();
        }

        $stats = [
            'users'              => DB::table('users')->count(),
            'businesses'         => DB::table('businesses')->count(),
            'jobs'               => $jobsCount,
            'destinations'       => DB::table('tourist_destinations')->count(),
            'food_businesses'    => $foodCount,
            'goods_businesses'   => $goodsCount,
            'service_businesses' => $servicesCount,
            'events'             => DB::table('events')->count(),
            'groups'             => DB::table('groups')->count(),
            'pending_applications' => $pendingApplicationsCount,
        ];

        return response(view('dashboard', ['user' => $user, 'stats' => $stats]))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
