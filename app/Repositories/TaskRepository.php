<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository
{
    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Find a task by ID or fail with relations.
     */
    public function findOrFail(int $id, array $relations = []): Task
    {
        return Task::with($relations)->findOrFail($id);
    }

    /**
     * Update an existing task.
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    /**
     * Delete a task.
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Get tasks by workspace ID with relations.
     */
    public function findByWorkspace(int $workspaceId, array $relations = []): Collection
    {
        return Task::with($relations)
            ->where('workspace_id', $workspaceId)
            ->get();
    }

    /**
     * Get root tasks by workspace ID with their sub-tasks.
     */
    public function findRootTasksByWorkspace(int $workspaceId): Collection
    {
        return Task::with(['creator', 'children.creator'])
            ->where('workspace_id', $workspaceId)
            ->whereNull('parent_id')
            ->get();
    }
}
