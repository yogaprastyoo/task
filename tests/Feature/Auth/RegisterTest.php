<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can register a new user', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Registration successful',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);

    $this->assertAuthenticated();
});

it('fails registration with missing name', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['name']);
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email']);
});

it('fails registration with case-insensitive duplicate email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'JOHN@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email']);
});

it('fails registration with password mismatch', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['password']);
});

it('does not return password in response', function () {
    $response = $this->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertStatus(201);
    $this->assertArrayNotHasKey('password', $response->json('data'));
});

it('restricts authenticated user from accessing register route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('Referer', 'http://localhost')
        ->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Already authenticated.',
        ]);
});
