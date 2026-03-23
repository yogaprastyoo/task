<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('can create a root workspace', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'My Workspace',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'My Workspace')
        ->assertJsonPath('data.owner_id', $this->user->id)
        ->assertJsonPath('data.parent_id', null)
        ->assertJsonPath('data.depth', 1);

    $this->assertDatabaseHas('workspaces', [
        'name' => 'My Workspace',
        'owner_id' => $this->user->id,
        'parent_id' => null,
        'depth' => 1,
    ]);
});

test('rejects duplicate workspace names at the root level for the same user', function () {
    Workspace::factory()->create([
        'name' => 'Duplicate',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Duplicate',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('allows different users to have workspaces with the same name at the root level', function () {
    Workspace::factory()->create([
        'name' => 'Same Name',
        'owner_id' => $this->otherUser->id,
        'parent_id' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Same Name',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);
});

test('can list workspaces for the authenticated user', function () {
    Workspace::factory()->count(3)->create(['owner_id' => $this->user->id]);
    Workspace::factory()->count(2)->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/workspaces');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can rename a workspace', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'Old Name',
        'owner_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/workspaces/{$workspace->id}", [
            'name' => 'New Name',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'New Name',
    ]);
});

test('can delete a workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
});

test('unauthorized users cannot update other users workspaces', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/workspaces/{$workspace->id}", [
            'name' => 'Hacked',
        ]);

    $response->assertStatus(403);
});

test('unauthorized users cannot delete other users workspaces', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(403);
});

test('it returns custom not found message when workspace is not found', function () {
    $response = $this->actingAs($this->user)
        ->putJson('/api/workspaces/9999', [
            'name' => 'New Name',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
        ]);
});

