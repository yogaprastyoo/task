<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'workspace_id' => Workspace::factory(),
            'creator_id' => User::factory(),
            'parent_id' => null,
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Indicate that the task has a parent.
     */
    public function withParent(Task $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'workspace_id' => $parent->workspace_id,
            'creator_id' => $parent->creator_id,
        ]);
    }

    public function todo(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Todo]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Done]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn () => ['priority' => TaskPriority::Low]);
    }

    public function mediumPriority(): static
    {
        return $this->state(fn () => ['priority' => TaskPriority::Medium]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => ['priority' => TaskPriority::High]);
    }
}
