@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Post a Job</h1>
        
        <form action="{{ route('jobs.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Job Title</label>
                <input type="text" name="title" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
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
                <label class="block text-gray-700 font-bold mb-2">Employment Type</label>
                <select name="employment_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
                    <option value="">Select Type</option>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                    <option value="temporary">Temporary</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Salary Min</label>
                    <input type="number" name="salary_min" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Salary Max</label>
                    <input type="number" name="salary_max" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Deadline</label>
                <input type="date" name="deadline" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    Post Job
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
