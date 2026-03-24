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

    $this->assertSoftDeleted('workspaces', ['id' => $workspace->id]);
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

    // Verify all are soft-deleted
    $this->assertSoftDeleted('workspaces', ['id' => $root->id]);
    $this->assertSoftDeleted('workspaces', ['id' => $child->id]);
    $this->assertSoftDeleted('workspaces', ['id' => $grandchild->id]);
});

test('moving a root workspace to root (same location) does not trigger name conflict', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'Root Workspace',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => null,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('moving a child workspace to the same parent does not trigger name conflict', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $workspace = Workspace::factory()->create([
        'name' => 'Child Workspace',
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('can retrieve a specific workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $workspace->id);
});

test('unauthorized users cannot view other users workspaces', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(403);
});

test('retrieving a workspace includes its children', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $child = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/workspaces/{$parent->id}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.children')
        ->assertJsonPath('data.children.0.id', $child->id);
});

test('can retrieve only root workspaces', function () {
    $root = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);
    Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $root->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/workspaces/root');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $root->id);
});

test('deleting a workspace soft deletes it and its children', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $child = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/workspaces/{$parent->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('workspaces', ['id' => $parent->id]);
    $this->assertSoftDeleted('workspaces', ['id' => $child->id]);

    // Ensure they are hidden from index
    $indexResponse = $this->actingAs($this->user)->getJson('/api/workspaces');
    $indexResponse->assertStatus(200)->assertJsonCount(0, 'data');
});

test('can restore a soft-deleted workspace and its children', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $child = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'parent_id' => $parent->id,
    ]);

    // Soft delete
    $this->actingAs($this->user)->deleteJson("/api/workspaces/{$parent->id}");

    // Restore
    $response = $this->actingAs($this->user)
        ->postJson("/api/workspaces/{$parent->id}/restore");

    $response->assertStatus(200);

    $this->assertDatabaseHas('workspaces', ['id' => $parent->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('workspaces', ['id' => $child->id, 'deleted_at' => null]);

    $indexResponse = $this->actingAs($this->user)->getJson('/api/workspaces');
    $indexResponse->assertStatus(200)->assertJsonCount(2, 'data');
});

test('can retrieve a list of trashed workspaces', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $trashWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $otherUserWorkspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);

    // Soft delete one
    $trashWorkspace->delete();
    $otherUserWorkspace->delete();

    $response = $this->actingAs($this->user)
        ->getJson('/api/workspaces/trash');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trashWorkspace->id);
});

test('can create a workspace with a name of a soft-deleted workspace', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'Soft Deleted Name',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    // Soft delete the workspace
    $this->actingAs($this->user)->deleteJson("/api/workspaces/{$workspace->id}");

    // Create a new one with the exact same name
    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Soft Deleted Name',
            'parent_id' => null,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);
});

test('cannot restore a workspace if an active workspace has the same name', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'Conflict Name',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    // Soft delete it
    $this->actingAs($this->user)->deleteJson("/api/workspaces/{$workspace->id}");

    // Create an active one with the same name
    Workspace::factory()->create([
        'name' => 'Conflict Name',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    // Try to restore the soft-deleted one
    $response = $this->actingAs($this->user)
        ->postJson("/api/workspaces/{$workspace->id}/restore");

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Cannot restore workspace because an active workspace with this name already exists at the root level.');
});

test('cannot restore an active workspace', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'Active Workspace',
        'owner_id' => $this->user->id,
        'parent_id' => null,
    ]);

    // Do NOT soft delete it. It is active.

    // Attempt to restore it
    $response = $this->actingAs($this->user)
        ->postJson("/api/workspaces/{$workspace->id}/restore");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Resource not found.');
});

// ============================================================
// SOFT DELETE EDGE CASES
// ============================================================

test('soft-deleted workspace is hidden from the index list', function () {
    Workspace::factory()->create(['owner_id' => $this->user->id]);
    $trashed = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $trashed->delete();

    $response = $this->actingAs($this->user)->getJson('/api/workspaces');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('soft-deleted workspace is hidden from the root list', function () {
    $trashed = Workspace::factory()->create(['owner_id' => $this->user->id, 'parent_id' => null]);
    Workspace::factory()->create(['owner_id' => $this->user->id, 'parent_id' => null]);
    $trashed->delete();

    $response = $this->actingAs($this->user)->getJson('/api/workspaces/root');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('cannot retrieve a soft-deleted workspace by id', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $workspace->delete();

    $response = $this->actingAs($this->user)
        ->getJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(404);
});

test('cannot update a soft-deleted workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $workspace->delete();

    $response = $this->actingAs($this->user)
        ->putJson("/api/workspaces/{$workspace->id}", [
            'name' => 'Updated Name',
        ]);

    $response->assertStatus(404);
});

test('cannot re-delete an already soft-deleted workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $workspace->delete();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(404);
});

test('cannot move a soft-deleted workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $newParent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $workspace->delete();

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $newParent->id,
        ]);

    $response->assertStatus(404);
});

test('cannot create a child under a soft-deleted parent', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $parent->delete();

    $response = $this->actingAs($this->user)
        ->postJson('/api/workspaces', [
            'name' => 'Orphan Child',
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(422);
});

test('cannot move a workspace to a soft-deleted parent', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $parent->delete();

    $response = $this->actingAs($this->user)
        ->patchJson("/api/workspaces/{$workspace->id}/move", [
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(422);
});

test('unauthorized user cannot restore another users trashed workspace', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->otherUser->id]);
    $workspace->delete();

    $response = $this->actingAs($this->user)
        ->postJson("/api/workspaces/{$workspace->id}/restore");

    $response->assertStatus(403);
});

test('children hidden from show response after being soft-deleted', function () {
    $parent = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $child = Workspace::factory()->create(['owner_id' => $this->user->id, 'parent_id' => $parent->id]);
    $child->delete();

    $response = $this->actingAs($this->user)
        ->getJson("/api/workspaces/{$parent->id}");

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data.children');
});

describe('Workspace Archiving and Settings (#41)', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('can create a workspace with visual settings', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/workspaces', [
                'name' => 'Design Workspace',
                'icon' => 'palette',
                'color' => '#FF5733',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Design Workspace')
            ->assertJsonPath('data.settings.icon', 'palette')
            ->assertJsonPath('data.settings.color', '#FF5733');

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Design Workspace',
            'owner_id' => $this->user->id,
        ]);

        $workspace = Workspace::where('name', 'Design Workspace')->first();
        expect($workspace->settings)->toBe(['icon' => 'palette', 'color' => '#FF5733']);
    });

    it('can update workspace visual settings', function () {
        $workspace = Workspace::factory()->create([
            'owner_id' => $this->user->id,
            'settings' => ['icon' => 'old-icon', 'color' => '#000000'],
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/workspaces/{$workspace->id}", [
                'icon' => 'new-icon',
                'color' => '#FFFFFF',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.settings.icon', 'new-icon')
            ->assertJsonPath('data.settings.color', '#FFFFFF');
    });

    it('can toggle archive status', function () {
        $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

        // Archive
        $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->id}/archive")
            ->assertStatus(200)
            ->assertJsonPath('data.is_archived', true);

        expect($workspace->refresh()->is_archived)->toBeTrue();

        // Unarchive
        $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->id}/archive")
            ->assertStatus(200)
            ->assertJsonPath('data.is_archived', false);

        expect($workspace->refresh()->is_archived)->toBeFalse();
    });

    it('excludes archived workspaces from default list', function () {
        Workspace::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Active WS',
            'is_archived' => false,
        ]);
        Workspace::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Archived WS',
            'is_archived' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/workspaces');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active WS');
    });

    it('can include archived workspaces in list with query parameter', function () {
        Workspace::factory()->create(['owner_id' => $this->user->id, 'is_archived' => false]);
        Workspace::factory()->create(['owner_id' => $this->user->id, 'is_archived' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/workspaces?include_archived=true');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('can retrieve only archived workspaces via dedicated endpoint', function () {
        Workspace::factory()->create(['owner_id' => $this->user->id, 'name' => 'Active', 'is_archived' => false]);
        Workspace::factory()->create(['owner_id' => $this->user->id, 'name' => 'Archived', 'is_archived' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/workspaces/archived');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Archived');
    });

    it('validates color hex format', function () {
        $this->actingAs($this->user)
            ->postJson('/api/workspaces', [
                'name' => 'Invalid Color',
                'color' => 'not-a-color',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    });

    it('prevents unauthorized archive toggle', function () {
        $otherUser = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->id}/archive")
            ->assertStatus(403);
    });
});
