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
            'due_date' => now()->addDays(2)->toDateString(),
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
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority', 'due_date']);
    });
});

describe('GET /api/workspaces/{workspaceId}/tasks', function () {
    it('can list tasks for a workspace', function () {
        Task::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/workspaces/{$this->workspace->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
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

    it('cannot update protected fields', function () {
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

        $response->assertStatus(200);
        $task->refresh();
        expect($task->workspace_id)->toBe($this->workspace->id);
        expect($task->status)->toBe(TaskStatus::Todo);
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
