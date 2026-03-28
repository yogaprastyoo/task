<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Handle the user registration request.
     */
    public function register(StoreRegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerUser($request->validated(), $request);

        return ApiResponse::success($user, 'Registration successful', 201);
    }

    /**
     * Handle the user login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->authService->login($request->validated(), $request);

        return ApiResponse::success(null, 'Login successful');
    }

    /**
     * Handle the user logout request.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponse::success(null, 'Logout successful');
    }
}
