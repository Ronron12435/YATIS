@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Events & Challenges</h1>
        @if(Auth::user()->role === 'admin')
            <a href="{{ route('events.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Create Event
            </a>
        @endif
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($events as $event)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
                @if($event->image)
                    <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->title }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                @endif
                
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $event->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($event->description, 100) }}</p>
                    
                    <div class="text-sm text-gray-600 mb-4">
                        <p><strong>Start:</strong> {{ $event->start_date->format('M d, Y') }}</p>
                        <p><strong>End:</strong> {{ $event->end_date->format('M d, Y') }}</p>
                    </div>

                    <a href="{{ route('events.show', $event->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                        View Event →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">No events available</p>
            </div>
        @endforelse
    </div>

    <!-- Leaderboard -->
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">🏆 Leaderboard</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-800">Rank</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-800">User</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-800">Points</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-800">Tasks Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaderboard as $index => $entry)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold">
                                    #{{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('profile.show', $entry['user']->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                    {{ $entry['user']->first_name }} {{ $entry['user']->last_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-bold text-lg">{{ $entry['total_points'] }}</td>
                            <td class="px-6 py-4">{{ $entry['tasks_completed'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No participants yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $events->links() }}
    </div>
</div>
@endsection
