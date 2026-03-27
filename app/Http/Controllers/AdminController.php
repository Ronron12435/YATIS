<?php

namespace App\Http\Controllers;

use App\DTOs\Auth\RegisterDTO;
use App\Services\AdminService;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService,
        private AuthService $authService
    ) {}

    public function dashboard()
    {
        $response = $this->adminService->getDashboard();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function businesses(Request $request)
    {
        $response = $this->adminService->getBusinesses(
            $request->input('search'),
            $request->input('type'),
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    public function events()
    {
        $response = $this->adminService->getEvents();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function deleteEvent($id)
    {
        $response = $this->adminService->deleteEvent((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function statistics()
    {
        $response = $this->adminService->getStatistics();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function createBusinessAccount(Request $request)
    {
        $validated = $request->validate([
            'username'   => 'required|string|max:255|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:6|confirmed',
        ]);

        $dto = new RegisterDTO(
            username: $validated['username'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            password: $validated['password'],
            role: 'business',
        );

        $response = $this->authService->register($dto);

        return response()->json($response->toArray(), $response->statusCode);
    }
}
