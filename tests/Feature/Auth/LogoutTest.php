<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('allows an authenticated user to log out', function () {
    $user = User::factory()->create();

    postJson('/api/auth/logout')
        ->assertUnauthorized(); // Ensure it requires authentication

    $response = $this->withHeader('Referer', 'http://localhost')->actingAs($user)->postJson('/api/auth/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logout successful',
            'data' => null,
        ]);

    assertGuest('web');
});

it('prevents a guest from logging out', function () {
    postJson('/api/auth/logout')
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});
