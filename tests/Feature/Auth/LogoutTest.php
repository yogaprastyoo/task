<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to log out', function () {
    $user = User::factory()->create();

    $response = $this->withHeader('Referer', 'http://localhost')
        ->actingAs($user)
        ->postJson('/api/auth/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logout successful',
            'data' => null,
        ]);
});

it('destroys the session on logout', function () {
    $user = User::factory()->create();

    $this->withHeader('Referer', 'http://localhost')
        ->actingAs($user)
        ->postJson('/api/auth/logout');

    $this->assertGuest('web');
});

it('prevents a guest from logging out', function () {
    $this->postJson('/api/auth/logout')
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});
