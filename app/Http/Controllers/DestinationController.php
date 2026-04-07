<?php

namespace App\Http\Controllers;

use App\DTOs\Destination\AddReviewDTO;
use App\DTOs\Destination\CreateDestinationDTO;
use App\Services\DestinationService;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function __construct(private DestinationService $destinationService) {}

    public function index(Request $request)
    {
        $response = $this->destinationService->getAll(
            $request->input('search'),
            $request->input('category'),
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string',
            'category'    => 'required|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'image'       => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('destinations', 'public');
        }

        $dto = new CreateDestinationDTO(
            name: $validated['name'],
            description: $validated['description'],
            location: $validated['location'],
            category: $validated['category'],
            latitude: $validated['latitude'] ?? null,
            longitude: $validated['longitude'] ?? null,
            image: $imagePath,
        );

        $response = $this->destinationService->create($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->destinationService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'string|max:255',
            'description' => 'string',
            'location'    => 'string',
            'category'    => 'string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'image'       => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('destinations', 'public');
        }

        $response = $this->destinationService->update((int) $id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy($id)
    {
        $response = $this->destinationService->delete((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function addReview(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'required|string|max:1000',
                'image'  => 'nullable|image|max:2048',
            ]);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('reviews', 'public');
            }

            $dto = new AddReviewDTO(
                destinationId: (int) $id,
                userId: $request->user()->id,
                rating: $validated['rating'],
                review: $validated['review'],
                image: $imagePath,
            );

            $response = $this->destinationService->addReview($dto);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding review: ' . $e->getMessage(),
                'data' => null,
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    public function reviews($id)
    {
        $response = $this->destinationService->getReviews((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function dashboardData(Request $request)
    {
        $response = $this->destinationService->getDashboardData($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
