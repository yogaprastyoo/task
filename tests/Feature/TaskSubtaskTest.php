<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    Sanctum::actingAs($this->user);
});

describe('POST /api/tasks/{parentId}/subtasks', function () {
    it('can create a sub-task under a parent task', function () {
        $parentTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$parentTask->id}/subtasks", [
            'title' => 'Sub Task',
            'description' => 'Sub Description',
            'priority' => 'low',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Sub Task')
            ->assertJsonPath('data.parent_id', $parentTask->id)
            ->assertJsonPath('data.workspace_id', $this->workspace->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Sub Task',
            'parent_id' => $parentTask->id,
            'workspace_id' => $this->workspace->id,
        ]);
    });

    it('cannot create a sub-task if parent is already a sub-task', function () {
        $rootTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $parentSubTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'parent_id' => $rootTask->id,
        ]);

        $response = $this->postJson("/api/tasks/{$parentSubTask->id}/subtasks", [
            'title' => 'Grandchild Task',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot create a sub-task under another sub-task. Maximum depth is 1 level.');
    });

    it('it cannot create a sub-task if parent ID is missing or invalid', function () {
        $response = $this->postJson('/api/tasks/invalid-id/subtasks', [
            'title' => 'Sub Task',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['parent_id']]);

        // Test huge numeric string that exceeds PHP_INT_MAX (causes TypeError typically)
        $responseHuge = $this->postJson('/api/tasks/99999999999999999999999999/subtasks', [
            'title' => 'Sub Task',
        ]);

        $responseHuge->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['parent_id']]);
    });

    it('cannot create a sub-task for a parent owned by another user', function () {
        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->postJson("/api/tasks/{$otherTask->id}/subtasks", [
            'title' => 'Unauthorized Sub-task',
        ]);

        $response->assertStatus(403);
    });
});

describe('PATCH /api/tasks/{id}/move validation', function () {
    it('cannot convert a parent with sub-tasks into a sub-task', function () {
        $parentTask = Task::factory()->create(['creator_id' => $this->user->id]);
        Task::factory()->create(['parent_id' => $parentTask->id, 'creator_id' => $this->user->id]);

        $otherParent = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $parentTask->workspace_id]);

        $response = $this->patchJson("/api/tasks/{$parentTask->id}/move", [
            'parent_id' => $otherParent->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot convert a parent task with sub-tasks into a sub-task.');
    });

    it('cannot set parent_id to a sub-task', function () {
        $root = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);
        $sub = Task::factory()->create(['parent_id' => $root->id, 'creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);

        $task = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);

        $response = $this->patchJson("/api/tasks/{$task->id}/move", [
            'parent_id' => $sub->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot set a sub-task as parent. Maximum depth is 1 level.');
    });

    it('cannot move to a parent in a different workspace', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
        $otherParent = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $otherWorkspace->id]);

        $response = $this->patchJson("/api/tasks/{$task->id}/move", [
            'parent_id' => $otherParent->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Parent task must be in the same workspace.');
    });

    it('cannot move task between workspaces via update', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'workspace_id' => $otherWorkspace->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['workspace_id']]);
    });

    it('cannot move a task to itself', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);

        $response = $this->patchJson("/api/tasks/{$task->id}/move", [
            'parent_id' => $task->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot move a task to itself.')
            ->assertJsonPath('errors.parent_id.0', 'Cannot move a task to itself.');
    });
});

describe('Review fix regression tests', function () {
    it('workspace_id in PUT /api/tasks/{id} is ignored and returns 200 without error', function () {
        // workspace_id is no longer a valid field; it must be stripped, not 422
        $task = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
            'workspace_id' => $otherWorkspace->id,
        ]);

        // workspace_id is marked as prohibited in UpdateTaskRequest, so it returns 422
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['workspace_id']]);

        // Confirm workspace was NOT changed
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'workspace_id' => $this->workspace->id,
        ]);
    });

    it('PATCH /api/tasks/{id}/move with invalid string ID returns 422, not 404 or 500', function () {
        $response = $this->patchJson('/api/tasks/not-a-valid-id/move', [
            'parent_id' => null,
        ]);

        // Non-numeric route segments do not match any task route, so Laravel returns 404
        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    });

    it('cannot set parent_id via PUT and have it silently saved', function () {
        $parent = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);
        $task = Task::factory()->create(['creator_id' => $this->user->id, 'workspace_id' => $this->workspace->id]);

        // Attempt to sneak parent_id via the standard update endpoint
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(200);

        // parent_id must not have been saved
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'parent_id' => null,
        ]);
    });
});
