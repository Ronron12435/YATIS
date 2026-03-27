<?php

namespace App\Http\Controllers;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username'   => 'required|string|max:255|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:8|confirmed',
            'role'       => 'nullable|in:user,business,employer,admin',
        ]);

        $dto = new RegisterDTO(
            username: $validated['username'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            password: $validated['password'],
            role: $validated['role'] ?? 'user',
        );

        $response = $this->authService->register($dto);

        Auth::login($response->data);

        return redirect('/dashboard')->with('success', $response->message);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $dto = new LoginDTO(email: $validated['email'], password: $validated['password']);

        $response = $this->authService->login($dto);

        if (!$response->success) {
            return back()->withErrors(['email' => $response->message])->withInput();
        }

        Auth::login($response->data);

        return redirect('/dashboard')->with('success', $response->message);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login')->with('success', 'Logged out successfully');
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()]);
    }
}
