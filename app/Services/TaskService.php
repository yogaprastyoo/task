<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskService
{
    public function __construct(
        protected TaskRepository $taskRepository,
        protected WorkspaceRepository $workspaceRepository
    ) {}

    /**
     * Create a new task in a workspace.
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
            $data['status'] = $data['status'] ?? TaskStatus::Todo->value;

            return $this->taskRepository->create($data);
        });
    }
}
