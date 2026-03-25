<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $service
    ) {}

    /**
     * Store a newly created task in storage.
     */
    public function store(int $workspaceId, StoreTaskRequest $request): JsonResponse
    {
        $task = $this->service->createTask(Auth::id(), $workspaceId, $request->validated());

        return ApiResponse::success($task, 'Task created successfully', 201);
    }
}
