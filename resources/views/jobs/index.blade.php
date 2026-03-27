@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Job Listings</h1>
        @if(Auth::user()->role === 'business')
            <a href="{{ route('jobs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Post a Job
            </a>
        @endif
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Search jobs..." value="{{ request('search') }}" class="border rounded px-3 py-2">
            <select name="employment_type" class="border rounded px-3 py-2">
                <option value="">All Types</option>
                <option value="full-time" {{ request('employment_type') === 'full-time' ? 'selected' : '' }}>Full-time</option>
                <option value="part-time" {{ request('employment_type') === 'part-time' ? 'selected' : '' }}>Part-time</option>
                <option value="contract" {{ request('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
            </select>
            <input type="text" name="location" placeholder="Location..." value="{{ request('location') }}" class="border rounded px-3 py-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
        </form>
    </div>

    <!-- Jobs List -->
    <div class="space-y-4">
        @forelse($jobs as $job)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">{{ $job->title }}</h3>
                        <p class="text-gray-600">{{ $job->business->business_name }}</p>
                    </div>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                        {{ ucfirst($job->employment_type) }}
                    </span>
                </div>

                <p class="text-gray-600 mb-4">{{ Str::limit($job->description, 200) }}</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                    <div>
                        <p class="text-gray-500">Location</p>
                        <p class="font-semibold">{{ $job->location }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Salary</p>
                        <p class="font-semibold">₱{{ number_format($job->salary_min, 0) }} - ₱{{ number_format($job->salary_max, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Deadline</p>
                        <p class="font-semibold">{{ $job->deadline->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Applications</p>
                        <p class="font-semibold">{{ $job->applications_count ?? 0 }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('jobs.show', $job->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                        View Details
                    </a>
                    @if(Auth::user()->role !== 'business' || Auth::id() !== $job->business->user_id)
                        <a href="{{ route('jobs.apply', $job->id) }}" class="text-green-600 hover:text-green-800 font-semibold">
                            Apply Now
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No jobs found</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $jobs->links() }}
    </div>
</div>
@endsection
