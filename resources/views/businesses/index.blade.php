@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Businesses</h1>
        @if(Auth::user()->role === 'business')
            <a href="{{ route('businesses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Register Business
            </a>
        @endif
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Search businesses..." value="{{ request('search') }}" class="border rounded px-3 py-2">
            <select name="business_type" class="border rounded px-3 py-2">
                <option value="">All Types</option>
                <option value="food" {{ request('business_type') === 'food' ? 'selected' : '' }}>Food</option>
                <option value="goods" {{ request('business_type') === 'goods' ? 'selected' : '' }}>Goods</option>
                <option value="services" {{ request('business_type') === 'services' ? 'selected' : '' }}>Services</option>
            </select>
            <input type="text" name="location" placeholder="Location..." value="{{ request('location') }}" class="border rounded px-3 py-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
        </form>
    </div>

    <!-- Businesses Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($businesses as $business)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $business->business_name }}</h3>
                    <p class="text-gray-600 text-sm mb-2">{{ $business->description }}</p>
                    
                    <div class="mb-4">
                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ ucfirst($business->business_type) }}
                        </span>
                        <span class="inline-block {{ $business->is_open ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-3 py-1 rounded-full text-sm font-semibold ml-2">
                            {{ $business->is_open ? 'Open' : 'Closed' }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-600 mb-4">
                        <p><strong>Phone:</strong> {{ $business->phone }}</p>
                        <p><strong>Email:</strong> {{ $business->email }}</p>
                        <p><strong>Address:</strong> {{ $business->address }}</p>
                    </div>

                    <a href="{{ route('businesses.show', $business->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                        View Details →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">No businesses found</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $businesses->links() }}
    </div>
</div>
@endsection
