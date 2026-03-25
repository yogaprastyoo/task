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
     * Find a task by ID or fail.
     */
    public function findOrFail(int $id): Task
    {
        return Task::findOrFail($id);
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
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Find tasks by workspace ID.
     */
    public function findByWorkspace(int $workspaceId): Collection
    {
        return Task::where('workspace_id', $workspaceId)->get();
    }
}
