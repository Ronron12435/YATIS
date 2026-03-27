@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Users</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['total_users'] ?? 0 }}</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Businesses</h3>
            <p class="text-3xl font-bold text-green-600">{{ $stats['total_businesses'] ?? 0 }}</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Posts</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['total_posts'] ?? 0 }}</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Events</h3>
            <p class="text-3xl font-bold text-orange-600">{{ $stats['total_events'] ?? 0 }}</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Today's Activity</h2>
            <ul class="space-y-2">
                <li class="flex justify-between">
                    <span>New Users:</span>
                    <span class="font-bold">{{ $stats['new_users_today'] ?? 0 }}</span>
                </li>
                <li class="flex justify-between">
                    <span>New Businesses:</span>
                    <span class="font-bold">{{ $stats['new_businesses_today'] ?? 0 }}</span>
                </li>
                <li class="flex justify-between">
                    <span>New Posts:</span>
                    <span class="font-bold">{{ $stats['new_posts_today'] ?? 0 }}</span>
                </li>
            </ul>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <div class="space-y-2">
                <a href="{{ route('admin.businesses') }}" class="block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-center">
                    Manage Businesses
                </a>
                <a href="{{ route('admin.events') }}" class="block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-center">
                    Manage Events
                </a>
                <a href="{{ route('admin.statistics') }}" class="block bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded text-center">
                    View Statistics
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
