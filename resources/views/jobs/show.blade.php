@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $job->title }}</h1>
                <p class="text-xl text-gray-600">{{ $job->business->business_name }}</p>
            </div>
            @if(Auth::id() === $job->business->user_id)
                <div class="flex gap-2">
                    <a href="{{ route('jobs.edit', $job->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Edit
                    </a>
                    <button onclick="deleteJob()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Delete
                    </button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Job Details</h3>
                <div class="space-y-3 text-gray-600">
                    <p><strong>Type:</strong> {{ ucfirst($job->employment_type) }}</p>
                    <p><strong>Location:</strong> {{ $job->location }}</p>
                    <p><strong>Salary:</strong> ₱{{ number_format($job->salary_min, 0) }} - ₱{{ number_format($job->salary_max, 0) }}</p>
                    <p><strong>Deadline:</strong> {{ $job->deadline->format('F d, Y') }}</p>
                    <p><strong>Status:</strong> <span class="{{ $job->is_active ? 'text-green-600' : 'text-red-600' }} font-semibold">{{ $job->is_active ? 'Active' : 'Closed' }}</span></p>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Info</h3>
                <div class="space-y-3 text-gray-600">
                    <p><strong>Phone:</strong> {{ $job->business->phone }}</p>
                    <p><strong>Email:</strong> {{ $job->business->email }}</p>
                    <p><strong>Address:</strong> {{ $job->business->address }}</p>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Description</h3>
            <p class="text-gray-600 whitespace-pre-wrap">{{ $job->description }}</p>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Requirements</h3>
            <p class="text-gray-600 whitespace-pre-wrap">{{ $job->requirements }}</p>
        </div>

        @if(Auth::user()->role !== 'business' || Auth::id() !== $job->business->user_id)
            <button onclick="openApplyModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 text-lg font-semibold">
                Apply for This Job
            </button>
        @else
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Applications ({{ $job->applications_count ?? 0 }})</h3>
                <a href="{{ route('jobs.applications', $job->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                    View All Applications →
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function deleteJob() {
    if(confirm('Delete this job posting?')) {
        fetch(`{{ route('jobs.destroy', $job->id) }}`, { 
            method: 'DELETE', 
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
        }).then(() => window.location.href = '{{ route('jobs.index') }}');
    }
}

function openApplyModal() {
    // Open modal for job application
    alert('Application form would open here');
}
</script>
@endsection
