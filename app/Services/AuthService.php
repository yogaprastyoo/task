<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Create a new AuthService instance.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Authenticate user credentials.
     *
     * @throws ValidationException
     */
    public function login(array $data): void
    {
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }
    }

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
