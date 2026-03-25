<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

describe('PATCH /api/tasks/{id}/status', function () {
    it('can update task status to in_progress', function () {
        $task = Task::factory()->create([
            'creator_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Task status updated successfully')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    });

    it('can update task status to done', function () {
        $task = Task::factory()->create([
            'creator_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'done',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'done');
    });

    it('cannot update status of another user\'s task', function () {
        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->patchJson("/api/tasks/{$otherTask->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(403);
    });

    it('validates required status', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonFragment(['status' => ['Status is required.']]);
    });

    it('validates enum status values', function () {
        $task = Task::factory()->create(['creator_id' => $this->user->id]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonFragment(['status' => ['Invalid status. Allowed values: todo, in_progress, done.']]);
    });

    it('returns 404 for non-existent task', function () {
        $response = $this->patchJson('/api/tasks/99999/status', [
            'status' => 'done',
        ]);

        $response->assertStatus(404);
    });
});
