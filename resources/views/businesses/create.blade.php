@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Create Business</h1>
        
        <form action="{{ route('businesses.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Business Name</label>
                <input type="text" name="business_name" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Business Type</label>
                <select name="business_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
                    <option value="">Select Type</option>
                    <option value="food">Food</option>
                    <option value="goods">Goods</option>
                    <option value="services">Services</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Description</label>
                <textarea name="description" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" rows="4" required></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Location</label>
                <input type="text" name="location" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Phone</label>
                <input type="tel" name="phone" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    Create Business
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
