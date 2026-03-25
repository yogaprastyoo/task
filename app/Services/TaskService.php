<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskService
{
    public function __construct(
        protected TaskRepository $taskRepository,
        protected WorkspaceRepository $workspaceRepository
    ) {}

    /**
     * Get all tasks in a workspace with ownership validation.
     */
    public function getTasksByWorkspace(int $userId, int $workspaceId): Collection
    {
        $workspace = $this->workspaceRepository->findOrFail($workspaceId);

        if ($workspace->owner_id !== $userId) {
            throw new HttpException(403, 'You do not own this workspace.');
        }

        return $this->taskRepository->findByWorkspace($workspaceId, ['creator', 'parent', 'children']);
    }

    /**
     * Create a task with workspace ownership validation.
     */
    public function createTask(int $userId, int $workspaceId, array $data): Task
    {
        return DB::transaction(function () use ($userId, $workspaceId, $data) {
            $workspace = $this->workspaceRepository->findOrFail($workspaceId);

            if ($workspace->owner_id !== $userId) {
                throw new HttpException(403, 'You do not own this workspace.');
            }

            $data['workspace_id'] = $workspaceId;
            $data['creator_id'] = $userId;
            $data['status'] = TaskStatus::Todo->value;

            return $this->taskRepository->create($data);
        });
    }

    /**
     * Find a single task with ownership validation.
     */
    public function findTask(int $userId, int $taskId): Task
    {
        $task = $this->taskRepository->findOrFail($taskId, ['workspace', 'creator', 'parent', 'children']);

        if ($task->creator_id !== $userId) {
            throw new HttpException(403, 'You do not own this task.');
        }

        return $task;
    }

    /**
     * Update a task with ownership validation.
     */
    public function updateTask(int $userId, int $taskId, array $data): Task
    {
        return DB::transaction(function () use ($userId, $taskId, $data) {
            $task = $this->taskRepository->findOrFail($taskId);

            if ($task->creator_id !== $userId) {
                throw new HttpException(403, 'You do not own this task.');
            }

            unset($data['workspace_id'], $data['creator_id'], $data['status']);

            return $this->taskRepository->update($task, $data);
        });
    }

    /**
     * Delete a task with ownership validation.
     */
    public function deleteTask(int $userId, int $taskId): void
    {
        DB::transaction(function () use ($userId, $taskId) {
            $task = $this->taskRepository->findOrFail($taskId);

            if ($task->creator_id !== $userId) {
                throw new HttpException(403, 'You do not own this task.');
            }

            $this->taskRepository->delete($task);
        });
    }
}
