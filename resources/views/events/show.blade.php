@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $event->title }}</h1>
                <p class="text-gray-600">{{ $event->description }}</p>
            </div>
            @if(Auth::user()->role === 'admin')
                <div class="flex gap-2">
                    <a href="{{ route('events.edit', $event->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Edit
                    </a>
                    <button onclick="deleteEvent()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Delete
                    </button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <p class="text-gray-500 text-sm">Start Date</p>
                <p class="text-lg font-semibold">{{ $event->start_date->format('F d, Y') }}</p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">End Date</p>
                <p class="text-lg font-semibold">{{ $event->end_date->format('F d, Y') }}</p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Status</p>
                <p class="text-lg font-semibold {{ $event->is_active ? 'text-green-600' : 'text-red-600' }}">
                    {{ $event->is_active ? 'Active' : 'Inactive' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Tasks -->
    <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Tasks</h2>
            @if(Auth::user()->role === 'admin')
                <button onclick="openTaskModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add Task
                </button>
            @endif
        </div>

        <div class="space-y-4">
            @forelse($event->tasks as $task)
                <div class="border rounded-lg p-6 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">{{ $task->title }}</h3>
                            <p class="text-gray-600">{{ $task->description }}</p>
                        </div>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ ucfirst(str_replace('_', ' ', $task->task_type)) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-600"><strong>Reward:</strong> {{ $task->reward_points }} points</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="completeTask({{ $task->id }})" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Complete Task
                            </button>
                            @if(Auth::user()->role === 'admin')
                                <button onclick="deleteTask({{ $task->id }})" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No tasks yet</p>
            @endforelse
        </div>
    </div>

    <!-- User Achievements -->
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Achievements</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($userAchievements as $achievement)
                <div class="border rounded-lg p-4 bg-gradient-to-r from-yellow-50 to-yellow-100">
                    <div class="flex items-center gap-4">
                        <div class="text-4xl">🏆</div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $achievement->task->title }}</p>
                            <p class="text-yellow-700 font-semibold">+{{ $achievement->points_earned }} points</p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No achievements yet. Complete tasks to earn points!</p>
            @endforelse
        </div>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-gray-800"><strong>Total Points:</strong> <span class="text-2xl font-bold text-blue-600">{{ $totalPoints }}</span></p>
            <p class="text-gray-800"><strong>Tasks Completed:</strong> <span class="text-2xl font-bold text-blue-600">{{ $tasksCompleted }}</span></p>
        </div>
    </div>
</div>

<script>
function completeTask(taskId) {
    fetch('{{ route('events.completeTask') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            event_id: {{ $event->id }},
            task_id: taskId,
            proof_data: {}
        })
    }).then(r => r.json()).then(data => {
        if(data.success) {
            alert('Task completed! You earned points!');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function deleteTask(taskId) {
    if(confirm('Delete this task?')) {
        fetch(`/events/tasks/${taskId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => location.reload());
    }
}

function deleteEvent() {
    if(confirm('Delete this event?')) {
        fetch(`{{ route('events.destroy', $event->id) }}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => window.location.href = '{{ route('events.index') }}');
    }
}
</script>
@endsection
