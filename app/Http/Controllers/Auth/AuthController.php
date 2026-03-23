<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

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
        $user = $this->authService->registerUser($request->validated());

        return ApiResponse::success($user, 'Registration successful', 201);
    }

    /**
     * Handle the user login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->authService->login($request->validated());

        $request->session()->regenerate();

        return ApiResponse::success(null, 'Login successful');
    }
}
