<?php

namespace App\Services;

use App\Repositories\TableRepository;
use App\Responses\ApiResponse;

class TableService
{
    public function __construct(private TableRepository $tableRepository) {}

    public function getByBusiness(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->tableRepository->getByBusiness($businessId), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $table = $this->tableRepository->findById($id);

        if (!$table) {
            return new ApiResponse(false, null, 'Table not found', 404);
        }

        return new ApiResponse(true, $table, 'Success');
    }

    public function create(int $authId, array $data): ApiResponse
    {
        $table = $this->tableRepository->findById($data['business_id'] ?? 0);

        // Authorization is handled via business ownership check in controller
        return new ApiResponse(true, $this->tableRepository->create($data), 'Table created', 201);
    }

    public function update(int $id, int $authId, array $data): ApiResponse
    {
        $table = $this->tableRepository->findById($id);

        if (!$table) {
            return new ApiResponse(false, null, 'Table not found', 404);
        }

        if ($authId !== $table->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->tableRepository->update($table, $data), 'Table updated');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $table = $this->tableRepository->findById($id);

        if (!$table) {
            return new ApiResponse(false, null, 'Table not found', 404);
        }

        if ($authId !== $table->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->tableRepository->delete($table);

        return new ApiResponse(true, null, 'Table deleted');
    }

    public function reserve(int $id, string $reservedUntil): ApiResponse
    {
        $table = $this->tableRepository->findById($id);

        if (!$table) {
            return new ApiResponse(false, null, 'Table not found', 404);
        }

        return new ApiResponse(true, $this->tableRepository->update($table, ['status' => 'reserved', 'reserved_until' => $reservedUntil]), 'Table reserved');
    }

    public function release(int $id, int $authId): ApiResponse
    {
        $table = $this->tableRepository->findById($id);

        if (!$table) {
            return new ApiResponse(false, null, 'Table not found', 404);
        }

        if ($authId !== $table->business->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->tableRepository->update($table, ['status' => 'available', 'reserved_until' => null]), 'Table released');
    }

    public function getAvailable(int $businessId): ApiResponse
    {
        return new ApiResponse(true, $this->tableRepository->getAvailable($businessId), 'Success');
    }
}
