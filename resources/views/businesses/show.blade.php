@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $business->business_name }}</h1>
                <p class="text-gray-600">{{ $business->description }}</p>
            </div>
            @if(Auth::id() === $business->user_id)
                <a href="{{ route('businesses.edit', $business->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Edit Business
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Information</h3>
                <div class="space-y-2 text-gray-600">
                    <p><strong>Type:</strong> {{ ucfirst($business->business_type) }}</p>
                    <p><strong>Phone:</strong> {{ $business->phone }}</p>
                    <p><strong>Email:</strong> {{ $business->email }}</p>
                    <p><strong>Address:</strong> {{ $business->address }}</p>
                    <p><strong>Status:</strong> <span class="{{ $business->is_open ? 'text-green-600' : 'text-red-600' }} font-semibold">{{ $business->is_open ? 'Open' : 'Closed' }}</span></p>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Hours</h3>
                <div class="space-y-2 text-gray-600">
                    <p><strong>Opening Time:</strong> {{ $business->opening_time ?? 'N/A' }}</p>
                    <p><strong>Closing Time:</strong> {{ $business->closing_time ?? 'N/A' }}</p>
                    <p><strong>Capacity:</strong> {{ $business->capacity ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Items / Products / Services -->
    @if($business->business_type === 'food')
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Menu Items</h2>
                @if(Auth::id() === $business->user_id)
                    <button onclick="openMenuModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Add Menu Item
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($business->menuItems as $item)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2">{{ $item->name }}</h4>
                        <p class="text-gray-600 text-sm mb-2">{{ $item->description }}</p>
                        <p class="text-lg font-bold text-green-600">₱{{ number_format($item->price, 2) }}</p>
                        @if(Auth::id() === $business->user_id)
                            <button onclick="deleteMenuItem({{ $item->id }})" class="text-red-600 hover:text-red-800 text-sm mt-2">Delete</button>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">No menu items yet</p>
                @endforelse
            </div>
        </div>
    @elseif($business->business_type === 'goods')
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Products</h2>
                @if(Auth::id() === $business->user_id)
                    <button onclick="openProductModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Add Product
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($business->products as $product)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2">{{ $product->name }}</h4>
                        <p class="text-gray-600 text-sm mb-2">{{ $product->description }}</p>
                        <p class="text-lg font-bold text-green-600">₱{{ number_format($product->price, 2) }}</p>
                        <p class="text-sm text-gray-600">Stock: {{ $product->stock }}</p>
                        @if(Auth::id() === $business->user_id)
                            <button onclick="deleteProduct({{ $product->id }})" class="text-red-600 hover:text-red-800 text-sm mt-2">Delete</button>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">No products yet</p>
                @endforelse
            </div>
        </div>
    @elseif($business->business_type === 'services')
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Services</h2>
                @if(Auth::id() === $business->user_id)
                    <button onclick="openServiceModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Add Service
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($business->services as $service)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2">{{ $service->name }}</h4>
                        <p class="text-gray-600 text-sm mb-2">{{ $service->description }}</p>
                        <p class="text-lg font-bold text-green-600">₱{{ number_format($service->price, 2) }}</p>
                        @if(Auth::id() === $business->user_id)
                            <button onclick="deleteService({{ $service->id }})" class="text-red-600 hover:text-red-800 text-sm mt-2">Delete</button>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">No services yet</p>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Tables for Food Businesses -->
    @if($business->business_type === 'food')
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Restaurant Tables</h2>
                @if(Auth::id() === $business->user_id)
                    <button onclick="openTablesModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Generate Tables
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse($business->tables as $table)
                    <div class="border rounded-lg p-4 text-center {{ $table->status === 'available' ? 'bg-green-50' : 'bg-red-50' }}">
                        <p class="font-bold text-lg">Table {{ $table->table_number }}</p>
                        <p class="text-sm text-gray-600">{{ $table->capacity }} seats</p>
                        <p class="text-sm font-semibold {{ $table->status === 'available' ? 'text-green-600' : 'text-red-600' }}">
                            {{ ucfirst($table->status) }}
                        </p>
                    </div>
                @empty
                    <p class="text-gray-500 col-span-full">No tables generated yet</p>
                @endforelse
            </div>
        </div>
    @endif
</div>

<script>
function deleteMenuItem(id) {
    if(confirm('Delete this menu item?')) {
        fetch(`/menu-items/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(() => location.reload());
    }
}

function deleteProduct(id) {
    if(confirm('Delete this product?')) {
        fetch(`/products/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(() => location.reload());
    }
}

function deleteService(id) {
    if(confirm('Delete this service?')) {
        fetch(`/services/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(() => location.reload());
    }
}
</script>
@endsection
