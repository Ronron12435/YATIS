@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Profile</h1>
        
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">First Name</label>
                <input type="text" name="first_name" value="{{ $user->first_name }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Last Name</label>
                <input type="text" name="last_name" value="{{ $user->last_name }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Bio</label>
                <textarea name="bio" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" rows="4">{{ $user->bio }}</textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Phone</label>
                <input type="tel" name="phone" value="{{ $user->phone ?? '' }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Location</label>
                <input type="text" name="location" value="{{ $user->location ?? '' }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="mb-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
