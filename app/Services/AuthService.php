<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Create a new AuthService instance.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Register a new user and log them in.
     */
    public function registerUser(array $data): User
    {
        $user = $this->userRepository->create($data);

        Auth::login($user);

        return $user;
    }
}
