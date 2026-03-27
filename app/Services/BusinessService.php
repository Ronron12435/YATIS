<?php

namespace App\Services;

use App\DTOs\Business\CreateBusinessDTO;
use App\Repositories\BusinessRepository;
use App\Responses\ApiResponse;

class BusinessService
{
    public function __construct(private BusinessRepository $businessRepository) {}

    public function getAll(?string $search, ?string $type, ?string $location): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->search($search, $type, $location), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $business = $this->businessRepository->findById($id);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        return new ApiResponse(true, $business, 'Success');
    }

    public function create(CreateBusinessDTO $dto): ApiResponse
    {
        $business = $this->businessRepository->create([
            'user_id'       => $dto->userId,
            'name'          => $dto->businessName,
            'category'      => $dto->businessType,
            'description'   => $dto->description,
            'address'       => $dto->address,
            'phone'         => $dto->phone,
            'email'         => $dto->email,
            'opening_time'  => $dto->openingTime,
            'closing_time'  => $dto->closingTime,
            'capacity'      => $dto->capacity,
            'latitude'      => $dto->latitude,
            'longitude'     => $dto->longitude,
            'logo'          => $dto->logo,
            'shop_image'    => $dto->shopImage,
        ]);

        return new ApiResponse(true, $business, 'Business registered successfully', 201);
    }

    public function update(int $id, int $authId, array $data): ApiResponse
    {
        $business = $this->businessRepository->findById($id);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->businessRepository->update($business, $data), 'Business updated successfully');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $business = $this->businessRepository->findById($id);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->businessRepository->delete($business);

        return new ApiResponse(true, null, 'Business deleted successfully');
    }

    public function isOpen(int $id): ApiResponse
    {
        $business = $this->businessRepository->findById($id);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if (!$business->opening_time || !$business->closing_time) {
            return new ApiResponse(true, ['is_open' => true], 'Success');
        }

        $now    = date('H:i:s');
        $isOpen = $now >= $business->opening_time && $now <= $business->closing_time;

        return new ApiResponse(true, ['is_open' => $isOpen], 'Success');
    }

    public function getHours(int $id): ApiResponse
    {
        $business = $this->businessRepository->findById($id);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        return new ApiResponse(true, [
            'opening_time' => $business->opening_time,
            'closing_time' => $business->closing_time,
        ], 'Success');
    }

    public function getByUser(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->getByUser($userId), 'Success');
    }

    public function addMenuItem(int $businessId, int $authId, array $data): ApiResponse
    {
        $business = $this->businessRepository->findById($businessId);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->businessRepository->createMenuItem(array_merge($data, ['business_id' => $businessId])), 'Menu item added', 201);
    }

    public function getMenuItems(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->getMenuItems($businessId), 'Success');
    }

    public function deleteMenuItem(int $itemId, int $authId): ApiResponse
    {
        $item = $this->businessRepository->findMenuItemById($itemId);

        if (!$item) {
            return new ApiResponse(false, null, 'Menu item not found', 404);
        }

        if ($authId !== $item->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->businessRepository->deleteMenuItem($item);

        return new ApiResponse(true, null, 'Menu item deleted');
    }

    public function addProduct(int $businessId, int $authId, array $data): ApiResponse
    {
        $business = $this->businessRepository->findById($businessId);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->businessRepository->createProduct(array_merge($data, ['business_id' => $businessId])), 'Product added', 201);
    }

    public function getProducts(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->getProducts($businessId), 'Success');
    }

    public function deleteProduct(int $productId, int $authId): ApiResponse
    {
        $product = $this->businessRepository->findProductById($productId);

        if (!$product) {
            return new ApiResponse(false, null, 'Product not found', 404);
        }

        if ($authId !== $product->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->businessRepository->deleteProduct($product);

        return new ApiResponse(true, null, 'Product deleted');
    }

    public function addService(int $businessId, int $authId, array $data): ApiResponse
    {
        $business = $this->businessRepository->findById($businessId);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->businessRepository->createService(array_merge($data, ['business_id' => $businessId])), 'Service added', 201);
    }

    public function getServices(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->businessRepository->getServices($businessId), 'Success');
    }

    public function deleteService(int $serviceId, int $authId): ApiResponse
    {
        $service = $this->businessRepository->findServiceById($serviceId);

        if (!$service) {
            return new ApiResponse(false, null, 'Service not found', 404);
        }

        if ($authId !== $service->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->businessRepository->deleteService($service);

        return new ApiResponse(true, null, 'Service deleted');
    }

    public function generateTables(int $businessId, int $authId, int $numberOfTables, int $seatsPerTable): ApiResponse
    {
        $business = $this->businessRepository->findById($businessId);

        if (!$business) {
            return new ApiResponse(false, null, 'Business not found', 404);
        }

        if ($authId !== $business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->businessRepository->generateTables($businessId, $numberOfTables, $seatsPerTable);

        return new ApiResponse(true, null, 'Tables generated successfully');
    }

    public function getBusinessesMap(): ApiResponse
    {
        $businesses = $this->businessRepository->getBusinessesMap();
        $counts = $this->businessRepository->getBusinessCountByType();

        return new ApiResponse(true, [
            'businesses' => $businesses,
            'counts' => $counts,
        ], 'Success');
    }
}
