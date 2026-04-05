<?php

namespace App\Http\Controllers;

use App\Services\TableService;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function __construct(private TableService $tableService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
        ]);

        $response = $this->tableService->getByBusiness((int) $validated['business_id']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id'  => 'required|exists:businesses,id',
            'table_number' => 'required|string',
            'capacity'     => 'required|integer|min:1',
        ]);

        $response = $this->tableService->create($request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->tableService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'table_number' => 'string',
            'capacity'     => 'integer|min:1',
            'status'       => 'in:available,reserved,occupied',
        ]);

        $response = $this->tableService->update((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->tableService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function reserve(Request $request, $id)
    {
        $validated = $request->validate([
            'reserved_until' => 'required|date|after:now',
        ]);

        $response = $this->tableService->reserve((int) $id, $validated['reserved_until']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function release(Request $request, $id)
    {
        $response = $this->tableService->release((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function available(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
        ]);

        $response = $this->tableService->getAvailable((int) $validated['business_id']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getByBusiness($businessId)
    {
        $response = $this->tableService->getByBusiness((int) $businessId);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
