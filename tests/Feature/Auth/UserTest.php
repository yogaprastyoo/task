<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns authenticated user in standardized format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/user');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Authenticated user',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
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
});

it('returns 401 for unauthenticated user on /api/user', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});
