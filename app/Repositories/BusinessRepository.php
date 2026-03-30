<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BusinessRepository
{
    public function search(?string $search, ?string $type, ?string $location): LengthAwarePaginator
    {
        $query = Business::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")->orWhere('description', 'like', "%$search%");
            });
        }

        if ($type) {
            $query->where('category', $type);
        }

        if ($location) {
            $query->where('address', 'like', "%$location%");
        }

        return $query->paginate(15);
    }

    public function findById(int $id): ?Business
    {
        return Business::find($id);
    }

    public function create(array $data): Business
    {
        return Business::create($data);
    }

    public function update(Business $business, array $data): Business
    {
        $business->update($data);
        return $business->fresh();
    }

    public function delete(Business $business): void
    {
        $business->delete();
    }

    public function getByUser(int $userId)
    {
        return Business::where('user_id', $userId)->get();
    }

    public function createMenuItem(array $data): MenuItem
    {
        return MenuItem::create($data);
    }

    public function getMenuItems(int $businessId): LengthAwarePaginator
    {
        return MenuItem::where('business_id', $businessId)->paginate(15);
    }

    public function findMenuItemById(int $id): ?MenuItem
    {
        return MenuItem::find($id);
    }

    public function deleteMenuItem(MenuItem $item): void
    {
        $item->delete();
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function getProducts(int $businessId): LengthAwarePaginator
    {
        return Product::where('business_id', $businessId)->paginate(15);
    }

    public function findProductById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    public function createService(array $data): Service
    {
        return Service::create($data);
    }

    public function getServices(int $businessId): LengthAwarePaginator
    {
        return Service::where('business_id', $businessId)->paginate(15);
    }

    public function findServiceById(int $id): ?Service
    {
        return Service::find($id);
    }

    public function deleteService(Service $service): void
    {
        $service->delete();
    }

    public function generateTables(int $businessId, int $numberOfTables, int $seatsPerTable): void
    {
        RestaurantTable::where('business_id', $businessId)->delete();

        for ($i = 1; $i <= $numberOfTables; $i++) {
            RestaurantTable::create([
                'business_id'  => $businessId,
                'table_number' => $i,
                'capacity'     => $seatsPerTable,
                'status'       => 'available',
            ]);
        }
    }

    public function getBusinessesMap()
    {
        $businesses = Business::select('id', 'name', 'category', 'latitude', 'longitude', 'address', 'phone', 'opening_time', 'closing_time')
            ->orderBy('name')
            ->get();

        // Sagay City bounds
        $sagayMinLat = 10.75;
        $sagayMaxLat = 11.05;
        $sagayMinLng = 123.30;
        $sagayMaxLng = 123.55;
        $sagayCenter = ['lat' => 10.8967, 'lng' => 123.4253];

        return $businesses->map(function ($business) use ($sagayMinLat, $sagayMaxLat, $sagayMinLng, $sagayMaxLng, $sagayCenter) {
            // If no coordinates or outside Sagay City, generate random coordinates within Sagay City
            $latitude = $business->latitude;
            $longitude = $business->longitude;

            if (!$latitude || !$longitude || $latitude < $sagayMinLat || $latitude > $sagayMaxLat || $longitude < $sagayMinLng || $longitude > $sagayMaxLng) {
                // Use business ID as seed for consistent but distributed coordinates
                mt_srand($business->id);
                $latitude = $sagayCenter['lat'] + (mt_rand(-500, 500) / 10000);
                $longitude = $sagayCenter['lng'] + (mt_rand(-500, 500) / 10000);
            }

            return [
                'id' => $business->id,
                'business_name' => $business->name,
                'business_type' => $business->category,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'address' => $business->address,
                'phone' => $business->phone,
                'opening_time' => $business->opening_time,
                'closing_time' => $business->closing_time,
            ];
        });
    }

    public function getBusinessCountByType()
    {
        return [
            'food' => Business::where('category', 'food')->count(),
            'goods' => Business::where('category', 'goods')->count(),
            'services' => Business::where('category', 'services')->count(),
        ];
    }
}
