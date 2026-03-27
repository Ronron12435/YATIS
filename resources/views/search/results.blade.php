@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Search Results</h1>

    <!-- Search Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" class="flex gap-4">
            <input type="text" name="q" placeholder="Search..." value="{{ request('q') }}" class="flex-1 border rounded px-3 py-2">
            <select name="type" class="border rounded px-3 py-2">
                <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>All</option>
                <option value="users" {{ request('type') === 'users' ? 'selected' : '' }}>Users</option>
                <option value="businesses" {{ request('type') === 'businesses' ? 'selected' : '' }}>Businesses</option>
                <option value="destinations" {{ request('type') === 'destinations' ? 'selected' : '' }}>Destinations</option>
                <option value="posts" {{ request('type') === 'posts' ? 'selected' : '' }}>Posts</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Search</button>
        </form>
    </div>

    <!-- Users Results -->
    @if(isset($results['users']) && count($results['users']) > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">👥 Users</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($results['users'] as $user)
                    <div class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h3 class="font-bold text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</h3>
                        <p class="text-gray-600 text-sm">@{{ $user->username }}</p>
                        <p class="text-gray-600 text-sm">{{ $user->email }}</p>
                        <a href="{{ route('profile.show', $user->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mt-2 inline-block">
                            View Profile →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Businesses Results -->
    @if(isset($results['businesses']) && count($results['businesses']) > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">🏢 Businesses</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($results['businesses'] as $business)
                    <div class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h3 class="font-bold text-gray-800">{{ $business->business_name }}</h3>
                        <p class="text-gray-600 text-sm">{{ $business->description }}</p>
                        <p class="text-gray-600 text-sm">{{ ucfirst($business->business_type) }}</p>
                        <a href="{{ route('businesses.show', $business->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mt-2 inline-block">
                            View Business →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Destinations Results -->
    @if(isset($results['destinations']) && count($results['destinations']) > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">🏖️ Destinations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($results['destinations'] as $destination)
                    <div class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h3 class="font-bold text-gray-800">{{ $destination->name }}</h3>
                        <p class="text-gray-600 text-sm">{{ $destination->description }}</p>
                        <p class="text-gray-600 text-sm">📍 {{ $destination->location }}</p>
                        <a href="{{ route('destinations.show', $destination->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mt-2 inline-block">
                            View Destination →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Posts Results -->
    @if(isset($results['posts']) && count($results['posts']) > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📝 Posts</h2>
            <div class="space-y-4">
                @foreach($results['posts'] as $post)
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-gray-800">{{ $post->user->first_name }} {{ $post->user->last_name }}</h3>
                            <span class="text-gray-500 text-sm">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-600">{{ Str::limit($post->content, 200) }}</p>
                        <a href="{{ route('posts.show', $post->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mt-2 inline-block">
                            Read More →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- No Results -->
    @if(empty($results) || (isset($results['users']) && isset($results['businesses']) && isset($results['destinations']) && isset($results['posts']) && count($results['users']) === 0 && count($results['businesses']) === 0 && count($results['destinations']) === 0 && count($results['posts']) === 0))
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No results found for "{{ request('q') }}"</p>
        </div>
    @endif
</div>
@endsection
