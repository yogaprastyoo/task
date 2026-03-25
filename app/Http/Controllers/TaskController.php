<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $service
    ) {}

    /**
     * Display a listing of tasks for a workspace.
     */
    public function index(int $workspaceId): JsonResponse
    {
        $tasks = $this->service->getTasksByWorkspace(Auth::id(), $workspaceId);

        return ApiResponse::success(TaskResource::collection($tasks), 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task.
     */
    public function store(int $workspaceId, StoreTaskRequest $request): JsonResponse
    {
        $task = $this->service->createTask(Auth::id(), $workspaceId, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task created successfully', 201);
    }

    /**
     * Display the specified task.
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->service->findTask(Auth::id(), $id);

        return ApiResponse::success(new TaskResource($task), 'Task retrieved successfully');
    }

    /**
     * Update the specified task.
     */
    public function update(int $id, UpdateTaskRequest $request): JsonResponse
    {
        $task = $this->service->updateTask(Auth::id(), $id, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task updated successfully');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteTask(Auth::id(), $id);

        return ApiResponse::success(null, 'Task deleted successfully');
    }

    /**
     * Update the status of the specified task.
     */
    public function status(int $task, UpdateTaskStatusRequest $request): JsonResponse
    {
        $task = $this->service->updateTaskStatus(Auth::id(), $task, $request->validated('status'));

        return ApiResponse::success(new TaskResource($task), 'Task status updated successfully');
    }
}
