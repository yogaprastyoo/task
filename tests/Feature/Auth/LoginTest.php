<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
            'data' => null,
        ]);

    $this->assertAuthenticatedAs($user);
});

it('cannot login with invalid password', function () {
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('cannot login with non-existent email', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('fails login validation for missing fields', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/login', []);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email', 'password']);
});

it('restricts authenticated user from accessing login route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Already authenticated.',
        ]);
});
