<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'    => 'required|string|min:2|max:255',
            'type' => 'nullable|in:users,businesses,posts,destinations,all',
        ]);

        $response = $this->searchService->search($validated['q'], $validated['type'] ?? 'all');
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function users(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:255'])['q'];
        $response = $this->searchService->searchUsers($q);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function businesses(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:255'])['q'];
        $response = $this->searchService->searchBusinesses($q);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destinations(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:255'])['q'];
        $response = $this->searchService->searchDestinations($q);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function posts(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:255'])['q'];
        $response = $this->searchService->searchPosts($q);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function advanced(Request $request)
    {
        return $this->index($request);
    }
}
