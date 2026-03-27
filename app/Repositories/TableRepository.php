<?php

namespace App\Repositories;

use App\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TableRepository
{
    public function getByBusiness(int $businessId): LengthAwarePaginator
    {
        return RestaurantTable::where('business_id', $businessId)->paginate(15);
    }

    public function findById(int $id): ?RestaurantTable
    {
        return RestaurantTable::with('business')->find($id);
    }

    public function create(array $data): RestaurantTable
    {
        return RestaurantTable::create($data);
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        $table->update($data);
        return $table->fresh();
    }

    public function delete(RestaurantTable $table): void
    {
        $table->delete();
    }

    public function getAvailable(int $businessId): LengthAwarePaginator
    {
        return RestaurantTable::where('business_id', $businessId)->where('status', 'available')->paginate(15);
    }
}
