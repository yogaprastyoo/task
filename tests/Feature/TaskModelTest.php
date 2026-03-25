<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Task Model Relationships', function () {
    it('belongs to a workspace', function () {
        $task = Task::factory()->create();

        expect($task->workspace)->toBeInstanceOf(Workspace::class);
    });

    it('belongs to a creator', function () {
        $task = Task::factory()->create();

        expect($task->creator)->toBeInstanceOf(User::class);
    });

    it('can have a parent task', function () {
        $parent = Task::factory()->create();
        $child = Task::factory()->withParent($parent)->create();

        expect($child->parent)->toBeInstanceOf(Task::class)
            ->and($child->parent->id)->toBe($parent->id);
    });

    it('can have children tasks', function () {
        $parent = Task::factory()->create();
        Task::factory()->count(2)->withParent($parent)->create();

        expect($parent->children)->toHaveCount(2)
            ->each->toBeInstanceOf(Task::class);
    });

    it('child inherits workspace and creator from parent', function () {
        $parent = Task::factory()->create();
        $child = Task::factory()->withParent($parent)->create();

        expect($child->workspace_id)->toBe($parent->workspace_id)
            ->and($child->creator_id)->toBe($parent->creator_id);
    });
});

describe('Task Model Casts', function () {
    it('casts status to TaskStatus enum', function () {
        $task = Task::factory()->create(['status' => 'todo']);

        expect($task->status)->toBe(TaskStatus::Todo);
    });

    it('casts priority to TaskPriority enum', function () {
        $task = Task::factory()->create(['priority' => 'high']);

        expect($task->priority)->toBe(TaskPriority::High);
    });
});

describe('Workspace & User Task Relationships', function () {
    it('workspace has many tasks', function () {
        $workspace = Workspace::factory()->create();
        Task::factory()->count(3)->create(['workspace_id' => $workspace->id]);

        expect($workspace->tasks)->toHaveCount(3);
    });

    it('user has many tasks as creator', function () {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['creator_id' => $user->id]);

        expect($user->tasks)->toHaveCount(2);
    });
});

describe('Task Factory States', function () {
    it('creates task with todo status', function () {
        $task = Task::factory()->todo()->create();
        expect($task->status)->toBe(TaskStatus::Todo);
    });

    it('creates task with in_progress status', function () {
        $task = Task::factory()->inProgress()->create();
        expect($task->status)->toBe(TaskStatus::InProgress);
    });

    it('creates task with done status', function () {
        $task = Task::factory()->done()->create();
        expect($task->status)->toBe(TaskStatus::Done);
    });

    it('creates task with low priority', function () {
        $task = Task::factory()->lowPriority()->create();
        expect($task->priority)->toBe(TaskPriority::Low);
    });

    it('creates task with high priority', function () {
        $task = Task::factory()->highPriority()->create();
        expect($task->priority)->toBe(TaskPriority::High);
    });
});
