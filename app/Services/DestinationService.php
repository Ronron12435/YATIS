<?php

namespace App\Services;

use App\DTOs\Destination\AddReviewDTO;
use App\DTOs\Destination\CreateDestinationDTO;
use App\Repositories\DestinationRepository;
use App\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;

class DestinationService
{
    public function __construct(private DestinationRepository $destinationRepository) {}

    public function getAll(?string $search, ?string $category): ApiResponse
    {
        return new ApiResponse(true, $this->destinationRepository->search($search, $category), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $destination = $this->destinationRepository->findById($id);

        if (!$destination) {
            return new ApiResponse(false, null, 'Destination not found', 404);
        }

        return new ApiResponse(true, $destination, 'Success');
    }

    public function create(CreateDestinationDTO $dto): ApiResponse
    {
        $destination = $this->destinationRepository->create([
            'name'        => $dto->name,
            'description' => $dto->description,
            'location'    => $dto->location,
            'category'    => $dto->category,
            'latitude'    => $dto->latitude,
            'longitude'   => $dto->longitude,
            'image'       => $dto->image,
        ]);

        return new ApiResponse(true, $destination, 'Destination created', 201);
    }

    public function update(int $id, array $data): ApiResponse
    {
        $destination = $this->destinationRepository->findById($id);

        if (!$destination) {
            return new ApiResponse(false, null, 'Destination not found', 404);
        }

        return new ApiResponse(true, $this->destinationRepository->update($destination, $data), 'Destination updated');
    }

    public function delete(int $id): ApiResponse
    {
        $destination = $this->destinationRepository->findById($id);

        if (!$destination) {
            return new ApiResponse(false, null, 'Destination not found', 404);
        }

        $this->destinationRepository->delete($destination);

        return new ApiResponse(true, null, 'Destination deleted');
    }

    public function addReview(AddReviewDTO $dto): ApiResponse
    {
        try {
            if (!$this->destinationRepository->findById($dto->destinationId)) {
                return new ApiResponse(false, null, 'Destination not found', 404);
            }

            $review = $this->destinationRepository->findOrCreateReview([
                'destination_id' => $dto->destinationId,
                'user_id'        => $dto->userId,
                'rating'         => $dto->rating,
                'review'         => $dto->review,
                'image'          => $dto->image,
            ]);

            return new ApiResponse(true, $review, 'Review added', 201);
        } catch (\Exception $e) {
            // Check if it's a unique constraint violation
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), 'unique_review') !== false) {
                
                try {
                    // Delete existing review
                    DB::table('destination_reviews')
                        ->where('destination_id', $dto->destinationId)
                        ->where('user_id', $dto->userId)
                        ->delete();
                    
                    // Create new review
                    $review = $this->destinationRepository->findOrCreateReview([
                        'destination_id' => $dto->destinationId,
                        'user_id'        => $dto->userId,
                        'rating'         => $dto->rating,
                        'review'         => $dto->review,
                        'image'          => $dto->image,
                    ]);
                    
                    return new ApiResponse(true, $review, 'Review updated', 201);
                } catch (\Exception $innerE) {
                    return new ApiResponse(false, null, 'Error updating review', 500);
                }
            }
            
            return new ApiResponse(false, null, 'Error adding review', 500);
        }
    }

    public function getReviews(int $id): ApiResponse
    {
        if (!$this->destinationRepository->findById($id)) {
            return new ApiResponse(false, null, 'Destination not found', 404);
        }

        return new ApiResponse(true, $this->destinationRepository->getReviews($id), 'Success');
    }

    public function getDashboardData(int $userId): ApiResponse
    {
        $destinations = $this->destinationRepository->getAllForDashboard();
        $total = $this->destinationRepository->totalCount();
        $myReviews = $this->destinationRepository->userReviewCount($userId);
        $myAvgRating = $this->destinationRepository->userAvgRating($userId);
        
        return new ApiResponse(true, [
            'destinations'   => $destinations,
            'total'          => $total,
            'my_reviews'     => $myReviews,
            'my_avg_rating'  => $myAvgRating,
        ], 'Success');
    }
}
