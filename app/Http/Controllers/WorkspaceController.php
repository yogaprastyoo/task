<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\MoveWorkspaceRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceService $service
    ) {}

    /**
     * Display a listing of trashed workspaces.
     */
    public function trash(): JsonResponse
    {
        $workspaces = $this->service->getTrashedWorkspaces(Auth::id());

        return ApiResponse::success($workspaces, 'Trashed workspaces retrieved successfully');
    }

    /**
     * Display a listing of archived workspaces.
     */
    public function archived(): JsonResponse
    {
        $archived = $this->service->getArchivedWorkspaces(Auth::id());

        return ApiResponse::success($archived, 'Archived workspaces retrieved successfully');
    }

    /**
     * Display a listing of the workspaces.
     */
    public function index(): JsonResponse
    {
        $includeArchived = request()->boolean('include_archived');
        $workspaces = $this->service->getWorkspaces(Auth::id(), $includeArchived);

        return ApiResponse::success($workspaces, 'Workspaces retrieved successfully');
    }

    /**
     * Display a listing of root workspaces (parent_id is null).
     */
    public function root(): JsonResponse
    {
        $includeArchived = request()->boolean('include_archived');
        $workspaces = $this->service->getRootWorkspaces(Auth::id(), $includeArchived);

        return ApiResponse::success($workspaces, 'Root workspaces retrieved successfully');
    }

    /**
     * Store a newly created workspace in storage.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->createWorkspace(Auth::id(), $request->validated());

        return ApiResponse::success($workspace, 'Workspace created successfully', 201);
    }

    /**
     * Update the specified workspace in storage.
     */
    public function update(int $id, UpdateWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->updateWorkspace(Auth::id(), $id, $request->validated());

        return ApiResponse::success($workspace, 'Workspace updated successfully');
    }

    /**
     * Display the specified workspace.
     */
    public function show(int $id): JsonResponse
    {
        $workspace = $this->service->findWorkspace(Auth::id(), $id);

        return ApiResponse::success($workspace, 'Workspace retrieved successfully');
    }

    /**
     * Remove the specified workspace from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteWorkspace(Auth::id(), $id);

        return ApiResponse::success(null, 'Workspace deleted successfully');
    }

    /**
     * Move the specified workspace to a new parent.
     */
    public function move(MoveWorkspaceRequest $request, int $id): JsonResponse
    {
        $workspace = $this->service->moveWorkspace(
            Auth::id(),
            $id,
            $request->validated('parent_id')
        );

        return ApiResponse::success($workspace, 'Workspace moved successfully');
    }

    /**
     * Restore a soft-deleted workspace.
     */
    public function restore(Workspace $workspace): JsonResponse
    {
        $workspace = $this->service->restoreWorkspace(Auth::id(), $workspace->id);

        return ApiResponse::success($workspace, 'Workspace restored successfully');
    }

    /**
     * Toggle archive status of a workspace.
     */
    public function archive(int $id): JsonResponse
    {
        $workspace = $this->service->archiveWorkspace(Auth::id(), $id);
        $status = $workspace->is_archived ? 'archived' : 'unarchived';

        return ApiResponse::success($workspace, "Workspace {$status} successfully");
    }
}
