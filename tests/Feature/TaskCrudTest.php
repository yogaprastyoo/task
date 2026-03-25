<?php

use App\Enums\TaskStatus;
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

describe('POST /api/workspaces/{workspaceId}/tasks', function () {
    it('can create a task in a workspace', function () {
        $response = $this->postJson("/api/workspaces/{$this->workspace->id}/tasks", [
            'title' => 'New Task',
            'description' => 'Description',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);
    });

    it('cannot create a task in another user\'s workspace', function () {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->postJson("/api/workspaces/{$otherWorkspace->id}/tasks", [
            'title' => 'Unauthorized Task',
        ]);

        $response->assertStatus(403);
    });

    it('validates task creation', function () {
        $response = $this->postJson("/api/workspaces/{$this->workspace->id}/tasks", [
            'title' => '',
            'priority' => 'invalid_priority',
            'due_date' => now()->subDay()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority', 'due_date']);
    });
});

describe('GET /api/workspaces/{workspaceId}/tasks', function () {
    it('can list tasks for a workspace as a nested tree', function () {
        $parentTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $childTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'parent_id' => $parentTask->id,
        ]);

        $response = $this->getJson("/api/workspaces/{$this->workspace->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data') // Only the root task should appear at top level
            ->assertJsonPath('data.0.id', $parentTask->id)
            ->assertJsonPath('data.0.children.0.id', $childTask->id); // Subtask is nested
    });

    it('cannot list tasks for another user\'s workspace', function () {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->getJson("/api/workspaces/{$otherWorkspace->id}/tasks");

        $response->assertStatus(403);
    });
});

describe('GET /api/tasks/{id}', function () {
    it('can show a task', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $task->id);
    });

    it('cannot show another user\'s task', function () {
        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->getJson("/api/tasks/{$otherTask->id}");

        $response->assertStatus(403);
    });
});

describe('PUT /api/tasks/{id}', function () {
    it('can update a task', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'priority' => 'low',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Task Title')
            ->assertJsonPath('data.priority', 'low');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
        ]);
    });

    it('cannot update protected fields like workspace_id', function () {
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
        $task = Task::factory()->create([
            'creator_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'status' => TaskStatus::Todo,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'workspace_id' => $otherWorkspace->id,
            'status' => 'done',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['workspace_id']]);
    });
});

describe('DELETE /api/tasks/{id}', function () {
    it('can delete a task', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    });

    it('cannot delete another user\'s task', function () {
        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/tasks/{$otherTask->id}");

        $response->assertStatus(403);
    });
});

describe('404 Not Found edge cases', function () {
    it('returns 404 when task does not exist', function () {
        $response = $this->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    });

    it('returns 404 when workspace does not exist on task creation', function () {
        $response = $this->postJson('/api/workspaces/99999/tasks', [
            'title' => 'Task for non-existent workspace',
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 when workspace does not exist on task listing', function () {
        $response = $this->getJson('/api/workspaces/99999/tasks');

        $response->assertStatus(404);
    });
});
