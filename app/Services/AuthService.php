<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $userRepository) {}

    public function register(RegisterDTO $dto): ApiResponse
    {
        $user = $this->userRepository->create([
            'username'   => $dto->username,
            'first_name' => $dto->firstName,
            'last_name'  => $dto->lastName,
            'email'      => $dto->email,
            'password'   => Hash::make($dto->password),
            'role'       => $dto->role,
        ]);

        return new ApiResponse(true, $user, 'Account created successfully', 201);
    }

    public function login(LoginDTO $dto): ApiResponse
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            return new ApiResponse(false, null, 'Invalid credentials', 401);
        }

        return new ApiResponse(true, $user, 'Login successful');
    }
}
