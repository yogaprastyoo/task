<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\MoveWorkspaceRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceService $service
    ) {}

    /**
     * Display a listing of the workspaces.
     */
    public function index(): JsonResponse
    {
        $workspaces = $this->service->getWorkspaces();

        return ApiResponse::success($workspaces, 'Workspaces retrieved successfully');
    }

    /**
     * Store a newly created workspace in storage.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->createWorkspace($request->validated());

        return ApiResponse::success($workspace, 'Workspace created successfully', 201);
    }

    /**
     * Update the specified workspace in storage.
     */
    public function update(int $id, UpdateWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->renameWorkspace($id, $request->name);

        return ApiResponse::success($workspace, 'Workspace renamed successfully');
    }

    /**
     * Remove the specified workspace from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteWorkspace($id);

        return ApiResponse::success(null, 'Workspace deleted successfully');
    }

    /**
     * Move the specified workspace to a new parent.
     */
    public function move(int $id, MoveWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->moveWorkspace($id, $request->parent_id);

        return ApiResponse::success($workspace, 'Workspace moved successfully');
    }
}
