<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreSubTaskRequest;
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
    public function index(int $workspace): JsonResponse
    {
        $tasks = $this->service->getTasksByWorkspace(Auth::id(), $workspace);

        return ApiResponse::success(TaskResource::collection($tasks), 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task.
     */
    public function store(int $workspace, StoreTaskRequest $request): JsonResponse
    {
        $taskModel = $this->service->createTask(Auth::id(), $workspace, $request->validated());

        return ApiResponse::success(new TaskResource($taskModel), 'Task created successfully', 201);
    }

    /**
     * Display the specified task.
     */
    public function show(int $task): JsonResponse
    {
        $taskModel = $this->service->findTask(Auth::id(), $task);

        return ApiResponse::success(new TaskResource($taskModel), 'Task retrieved successfully');
    }

    /**
     * Update the specified task.
     */
    public function update(int $task, UpdateTaskRequest $request): JsonResponse
    {
        $taskModel = $this->service->updateTask(Auth::id(), $task, $request->validated());

        return ApiResponse::success(new TaskResource($taskModel), 'Task updated successfully');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $task): JsonResponse
    {
        $this->service->deleteTask(Auth::id(), $task);

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

    /**
     * Create a sub-task under a parent task.
     */
    public function subtask(int $parentId, StoreSubTaskRequest $request): JsonResponse
    {
        $task = $this->service->createSubTask(Auth::id(), $parentId, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Sub-task created successfully', 201);
    }

    /**
     * Move a task to a different parent.
     */
    public function move(int $taskId, MoveTaskRequest $request): JsonResponse
    {
        $task = $this->service->moveTask(Auth::id(), $taskId, $request->validated('parent_id'));

        return ApiResponse::success(new TaskResource($task), 'Task moved successfully');
    }
}
