<?php

namespace App\Repositories;

use App\Models\DestinationReview;
use App\Models\TouristDestination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DestinationRepository
{
    public function search(?string $search, ?string $category): LengthAwarePaginator
    {
        $query = TouristDestination::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")->orWhere('description', 'like', "%$search%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        return $query->paginate(15);
    }

    public function findById(int $id): ?TouristDestination
    {
        return TouristDestination::find($id);
    }

    public function create(array $data): TouristDestination
    {
        return TouristDestination::create($data);
    }

    public function update(TouristDestination $destination, array $data): TouristDestination
    {
        $destination->update($data);
        return $destination->fresh();
    }

    public function delete(TouristDestination $destination): void
    {
        $destination->delete();
    }

    public function createReview(array $data): DestinationReview
    {
        return DestinationReview::create($data);
    }

    public function findOrCreateReview(array $data): DestinationReview
    {
        // Create a new review each time (allows multiple reviews per user per destination)
        // The unique constraint will be removed via migration
        return DestinationReview::create([
            'destination_id' => $data['destination_id'],
            'user_id'        => $data['user_id'],
            'rating'         => $data['rating'],
            'review'         => $data['review'],
            'image'          => $data['image'] ?? null,
        ]);
    }

    public function updateRating(int $destinationId): void
    {
        // Rating columns are optional - system works without them
        // Reviews are fetched dynamically from API
    }

    public function totalCount(): int
    {
        return TouristDestination::count();
    }

    public function userReviewCount(int $userId): int
    {
        try {
            return DB::table('destination_reviews')->where('user_id', $userId)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function userAvgRating(int $userId): float
    {
        try {
            $avg = DB::table('destination_reviews')->where('user_id', $userId)->avg('rating');
            return $avg ? round($avg, 1) : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    public function getAllForDashboard(): array
    {
        $destinations = TouristDestination::orderBy('name')->get();
        $result = [];
        
        foreach ($destinations as $dest) {
            try {
                $reviewCount = DB::table('destination_reviews')
                    ->where('destination_id', $dest->id)
                    ->count();
                
                $avgRating = DB::table('destination_reviews')
                    ->where('destination_id', $dest->id)
                    ->avg('rating');
            } catch (\Exception $e) {
                $reviewCount = 0;
                $avgRating = null;
            }
            
            $result[] = [
                'id' => $dest->id,
                'name' => $dest->name,
                'description' => $dest->description,
                'location' => $dest->location,
                'address' => $dest->address,
                'category' => $dest->category,
                'latitude' => $dest->latitude,
                'longitude' => $dest->longitude,
                'image' => $dest->image,
                'rating' => $avgRating ? round($avgRating, 1) : 0,
                'reviews_count' => $reviewCount,
            ];
        }
        
        return $result;
    }

    public function getReviews(int $destinationId): LengthAwarePaginator
    {
        return DB::table('destination_reviews as dr')
            ->join('users as u', 'u.id', '=', 'dr.user_id')
            ->select('dr.id', 'dr.rating', 'dr.review', 'dr.created_at', 'u.id as user_id', 'u.username', 'u.first_name', 'u.last_name', 'u.profile_picture')
            ->where('dr.destination_id', $destinationId)
            ->latest('dr.created_at')
            ->paginate(10);
    }
}
