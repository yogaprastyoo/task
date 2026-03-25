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

        return $this->taskRepository->findRootTasksByWorkspace($workspaceId);
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

            if (array_key_exists('workspace_id', $data)) {
                throw new HttpException(422, 'Workspace cannot be changed after task creation.');
            }

            unset($data['workspace_id'], $data['creator_id'], $data['status'], $data['parent_id']);

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

    /**
     * Update task status with ownership validation.
     */
    public function updateTaskStatus(int $userId, int $taskId, string $status): Task
    {
        return DB::transaction(function () use ($userId, $taskId, $status) {
            $task = $this->taskRepository->findOrFail($taskId);

            if ($task->creator_id !== $userId) {
                throw new HttpException(403, 'You do not own this task.');
            }

            return $this->taskRepository->update($task, ['status' => $status]);
        });
    }

    /**
     * Create a sub-task under a parent task.
     */
    public function createSubTask(int $userId, int $parentId, array $data): Task
    {
        return DB::transaction(function () use ($userId, $parentId, $data) {
            $parent = $this->taskRepository->findOrFail($parentId);

            if ($parent->creator_id !== $userId) {
                throw new HttpException(403, 'You do not own this task.');
            }

            if ($parent->parent_id !== null) {
                throw new HttpException(422, 'Cannot create a sub-task under another sub-task. Maximum depth is 1 level.');
            }

            $data['parent_id'] = $parent->id;
            $data['workspace_id'] = $parent->workspace_id;
            $data['creator_id'] = $userId;
            $data['status'] = TaskStatus::Todo->value;

            return $this->taskRepository->create($data);
        });
    }

    /**
     * Move a task to a different parent within the same workspace.
     */
    public function moveTask(int $userId, int $taskId, ?int $parentId): Task
    {
        return DB::transaction(function () use ($userId, $taskId, $parentId) {
            $task = $this->taskRepository->findOrFail($taskId);

            if ($task->creator_id !== $userId) {
                throw new HttpException(403, 'You do not own this task.');
            }

            if ($parentId !== null) {
                $parent = $this->taskRepository->findOrFail($parentId);

                if ($parent->workspace_id !== $task->workspace_id) {
                    throw new HttpException(422, 'Parent task must be in the same workspace.');
                }

                if ($parent->parent_id !== null) {
                    throw new HttpException(422, 'Cannot set a sub-task as parent. Maximum depth is 1 level.');
                }

                if ($task->children()->count() > 0) {
                    throw new HttpException(422, 'Cannot convert a parent task with sub-tasks into a sub-task.');
                }
            }

            return $this->taskRepository->update($task, ['parent_id' => $parentId]);
        });
    }
}
