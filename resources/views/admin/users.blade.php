@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" placeholder="Search users..." value="{{ request('search') }}" class="border rounded px-3 py-2">
            <select name="role" class="border rounded px-3 py-2">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                <option value="business" {{ request('role') === 'business' ? 'selected' : '' }}>Business</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Name</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Email</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Username</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Role</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Joined</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-800">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">{{ $user->username }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : ($user->role === 'business' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <button onclick="deleteUser({{ $user->id }})" class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $users->links() }}
    </div>
</div>

<script>
function deleteUser(userId) {
    if(confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch(`{{ route('admin.deleteUser', '') }}/${userId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => location.reload());
    }
}
</script>
@endsection
