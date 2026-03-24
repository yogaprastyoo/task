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

test('can create a level 2 workspace', function () {
    $root = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Child Workspace',
            'parent_id' => $root->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.depth', 2)
        ->assertJsonPath('data.parent_id', $root->id);
});

test('can create a level 3 workspace', function () {
    $root = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $level2 = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $root->id,
        'depth' => 2,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Grandchild Workspace',
            'parent_id' => $level2->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.depth', 3)
        ->assertJsonPath('data.parent_id', $level2->id);
});

test('cannot create a level 4 workspace due to depth limit', function () {
    $root = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $level2 = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $root->id,
        'depth' => 2,
    ]);
    $level3 = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $level2->id,
        'depth' => 3,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Too Deep',
            'parent_id' => $level3->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Maximum workspace depth of 3 reached.');
});

test('cannot create a child under a parent owned by another user', function () {
    $othersWorkspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Attempted Theft',
            'parent_id' => $othersWorkspace->id,
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Parent workspace does not belong to you.');
});

test('cannot create a duplicate name under the same parent', function () {
    $root = Workspace::factory()->create(['owner_id' => $this->user->id]);
    Workspace::factory()->create([
        'name' => 'Sibling',
        'owner_id' => $this->user->id,
        'parent_id' => $root->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Sibling',
            'parent_id' => $root->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('allows same name under different parents', function () {
    $root1 = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $root2 = Workspace::factory()->create(['owner_id' => $this->user->id]);

    Workspace::factory()->create([
        'name' => 'Non-Sibling',
        'owner_id' => $this->user->id,
        'parent_id' => $root1->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Non-Sibling',
            'parent_id' => $root2->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);
});

test('can move a root workspace to be a child of another workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $newParent = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $newParent->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.parent_id', $newParent->id)
        ->assertJsonPath('data.depth', 2);

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'parent_id' => $newParent->id,
        'depth' => 2,
    ]);
});

test('can move a child workspace to be a root workspace', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $workspace = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
        'depth' => 2,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => null,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.parent_id', null)
        ->assertJsonPath('data.depth', 1);
});

test('cannot move a workspace to itself', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $workspace->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cannot move a workspace to itself.');
});

test('cannot move a workspace to its own descendant (circular)', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $child = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
        'depth' => 2,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$parent->id}/move", [
            'parent_id' => $child->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Circular dependency detected: Cannot move a workspace to its own descendant.');
});

test('recalculates depth for the whole subtree after move', function () {
    $oldRoot = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $workspace = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $oldRoot->id,
        'depth' => 2,
    ]);
    $descendant = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $workspace->id,
        'depth' => 3,
    ]);

    $newRoot = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $newRoot->id,
        ]);

    $response->assertStatus(200);

    // Workspace should now be at depth 2 (child of newRoot at depth 1)
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'depth' => 2,
        'parent_id' => $newRoot->id,
    ]);

    // Descendant should now be at depth 3
    $this->assertDatabaseHas('workspaces', [
        'id' => $descendant->id,
        'depth' => 3,
        'parent_id' => $workspace->id,
    ]);
});

test('blocks move if it would exceed depth limit', function () {
    // Level 1 -> Level 2
    $movedParent = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $movedChild = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $movedParent->id,
        'depth' => 2,
    ]);

    // Destination is at Level 2
    $destRoot = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $destParent = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $destRoot->id,
        'depth' => 2,
    ]);

    // Moving $movedParent (height 2) under $destParent (depth 2) would result in depth 4 (2+2)
    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$movedParent->id}/move", [
            'parent_id' => $destParent->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Moving this workspace would exceed the maximum depth of 3.');
});

test('cannot move workspace owned by another user', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => null,
        ]);

    $response->assertStatus(403);
});

test('cannot move workspace to a parent where a sibling with the same name exists', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);

    // Existing sibling under $parent
    Workspace::factory()->create([
        'name' => 'Conflict Name',
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
        'depth' => 2,
    ]);

    // Workspace to move (currently root)
    $workspace = Workspace::factory()->create([
        'name' => 'Conflict Name',
        'owner_id' => $this->user->id,
        'depth' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'A workspace with this name already exists at this parent level.');
});

test('deleting a workspace also deletes all its descendants', function () {
    // Root -> Child -> Grandchild
    $root = Workspace::factory()->create(['owner_id' => $this->user->id, 'depth' => 1]);
    $child = Workspace::factory()->create(['owner_id' => $this->user->id, 'parent_id' => $root->id, 'depth' => 2]);
    $grandchild = Workspace::factory()->create(['owner_id' => $this->user->id, 'parent_id' => $child->id, 'depth' => 3]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/workspaces/{$root->id}");

    $response->assertStatus(200);

    // Verify all are deleted
    $this->assertDatabaseMissing('workspaces', ['id' => $root->id]);
    $this->assertDatabaseMissing('workspaces', ['id' => $child->id]);
    $this->assertDatabaseMissing('workspaces', ['id' => $grandchild->id]);
});
