<?php

namespace App\Http\Controllers;

use App\DTOs\Business\CreateBusinessDTO;
use App\Services\BusinessService;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function __construct(private BusinessService $businessService) {}

    public function index(Request $request)
    {
        $response = $this->businessService->getAll(
            $request->input('search'),
            $request->input('business_type'),
            $request->input('location'),
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:food,goods,services',
            'description'   => 'nullable|string',
            'address'       => 'required|string',
            'phone'         => 'required|string|regex:/^\d{11}$/',
            'email'         => 'required|email',
            'opening_time'  => 'nullable|date_format:H:i:s',
            'closing_time'  => 'nullable|date_format:H:i:s',
            'capacity'      => 'nullable|integer|min:1',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'logo'          => 'nullable|image|max:2048',
            'shop_image'    => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'Phone number must be exactly 11 digits.',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('businesses/logos', 'public');
        }

        if ($request->hasFile('shop_image')) {
            $validated['shop_image'] = $request->file('shop_image')->store('businesses/images', 'public');
        }

        $dto = new CreateBusinessDTO(
            userId: $request->user()->id,
            businessName: $validated['business_name'],
            businessType: $validated['business_type'],
            address: $validated['address'],
            phone: $validated['phone'],
            email: $validated['email'],
            description: $validated['description'] ?? null,
            openingTime: $validated['opening_time'] ?? null,
            closingTime: $validated['closing_time'] ?? null,
            capacity: $validated['capacity'] ?? null,
            latitude: $validated['latitude'] ?? null,
            longitude: $validated['longitude'] ?? null,
            logo: $validated['logo'] ?? null,
            shopImage: $validated['shop_image'] ?? null,
        );

        $response = $this->businessService->create($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->businessService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'business_name' => 'string|max:255',
            'description'   => 'nullable|string',
            'address'       => 'string',
            'phone'         => 'string|regex:/^\d{11}$/',
            'email'         => 'email',
            'opening_time'  => 'nullable|date_format:H:i:s',
            'closing_time'  => 'nullable|date_format:H:i:s',
            'capacity'      => 'nullable|integer|min:1',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'logo'          => 'nullable|image|max:2048',
            'shop_image'    => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'Phone number must be exactly 11 digits.',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('businesses/logos', 'public');
        }

        if ($request->hasFile('shop_image')) {
            $validated['shop_image'] = $request->file('shop_image')->store('businesses/images', 'public');
        }

        $response = $this->businessService->update((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->businessService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function isOpen($id)
    {
        $response = $this->businessService->isOpen((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function hours($id)
    {
        $response = $this->businessService->getHours((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function myBusinesses(Request $request)
    {
        $response = $this->businessService->getByUser($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function addMenuItem(Request $request, $businessId)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('menu-items', 'public');
        }

        $response = $this->businessService->addMenuItem((int) $businessId, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getMenuItems($businessId)
    {
        $response = $this->businessService->getMenuItems((int) $businessId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function deleteMenuItem(Request $request, $itemId)
    {
        $response = $this->businessService->deleteMenuItem((int) $itemId, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function addProduct(Request $request, $businessId)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $response = $this->businessService->addProduct((int) $businessId, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getProducts($businessId)
    {
        $response = $this->businessService->getProducts((int) $businessId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function deleteProduct(Request $request, $productId)
    {
        $response = $this->businessService->deleteProduct((int) $productId, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function addService(Request $request, $businessId)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $response = $this->businessService->addService((int) $businessId, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getServices($businessId)
    {
        $response = $this->businessService->getServices((int) $businessId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function deleteService(Request $request, $serviceId)
    {
        $response = $this->businessService->deleteService((int) $serviceId, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function generateTables(Request $request, $businessId)
    {
        $validated = $request->validate([
            'number_of_tables' => 'required|integer|min:1|max:100',
            'seats_per_table'  => 'required|integer|min:1|max:20',
        ]);

        $response = $this->businessService->generateTables(
            (int) $businessId,
            $request->user()->id,
            $validated['number_of_tables'],
            $validated['seats_per_table'],
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function map()
    {
        $response = $this->businessService->getBusinessesMap();
        return response()->json($response->toArray(), $response->statusCode);
    }
}
