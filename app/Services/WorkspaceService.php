<?php

namespace App\Services;

use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class WorkspaceService
{
    public function __construct(
        protected WorkspaceRepository $repository
    ) {}

    /**
     * Get all workspaces for the authenticated user.
     */
    public function getWorkspaces(): Collection
    {
        return $this->repository->getByOwner(Auth::id());
    }

    /**
     * Create a new root-level workspace.
     */
    public function createWorkspace(array $data): Workspace
    {
        $ownerId = Auth::id();
        $parentId = null; // Enforced root-level for Level 1 (Issue #22)
        $depth = 1;

        if ($this->repository->findByNameAndParent($ownerId, $parentId, $data['name'])) {
            throw new Exception('A workspace with this name already exists at the root level.', 422);
        }

        return $this->repository->create([
            'name' => $data['name'],
            'owner_id' => $ownerId,
            'parent_id' => $parentId,
            'depth' => $depth,
        ]);
    }

    /**
     * Rename an existing workspace.
     */
    public function renameWorkspace(int $id, string $name): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== Auth::id()) {
            throw new Exception('Unauthorized to update this workspace.', 403);
        }

        if ($name !== $workspace->name) {
            if ($this->repository->findByNameAndParent(Auth::id(), $workspace->parent_id, $name)) {
                throw new Exception('A workspace with this name already exists at this level.', 422);
            }
        }

        return $this->repository->update($workspace, ['name' => $name]);
    }

    /**
     * Delete a workspace.
     */
    public function deleteWorkspace(int $id): void
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== Auth::id()) {
            throw new Exception('Unauthorized to delete this workspace.', 403);
        }

        $this->repository->delete($workspace);
    }
}
