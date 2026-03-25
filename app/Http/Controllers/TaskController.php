<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
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

        return ApiResponse::success($tasks, 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task.
     */
    public function store(int $workspaceId, StoreTaskRequest $request): JsonResponse
    {
        $task = $this->service->createTask(Auth::id(), $workspaceId, $request->validated());

        return ApiResponse::success($task, 'Task created successfully', 201);
    }

    /**
     * Display the specified task.
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->service->findTask(Auth::id(), $id);

        return ApiResponse::success($task, 'Task retrieved successfully');
    }

    /**
     * Update the specified task.
     */
    public function update(int $id, UpdateTaskRequest $request): JsonResponse
    {
        $task = $this->service->updateTask(Auth::id(), $id, $request->validated());

        return ApiResponse::success($task, 'Task updated successfully');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteTask(Auth::id(), $id);

        return ApiResponse::success(null, 'Task deleted successfully');
    }
}
