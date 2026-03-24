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
     * Create a new workspace (Root or Child).
     */
    public function createWorkspace(array $data): Workspace
    {
        $ownerId = Auth::id();
        $parentId = $data['parent_id'] ?? null;
        $depth = 1;

        if ($parentId) {
            $parent = $this->repository->findOrFail($parentId);

            if ($parent->owner_id !== $ownerId) {
                throw new Exception('Parent workspace does not belong to you.', 403);
            }

            $depth = $parent->depth + 1;
        }

        if ($depth > 3) {
            throw new Exception('Maximum workspace depth of 3 reached.', 422);
        }

        if ($this->repository->findByNameAndParent($ownerId, $parentId, $data['name'])) {
            $levelMessage = $parentId ? 'at this parent level' : 'at the root level';
            throw new Exception("A workspace with this name already exists {$levelMessage}.", 422);
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
                $levelMessage = $workspace->parent_id ? 'at this parent level' : 'at the root level';
                throw new Exception("A workspace with this name already exists {$levelMessage}.", 422);
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

    /**
     * Move a workspace to a new parent.
     */
    public function moveWorkspace(int $id, ?int $parentId): Workspace
    {
        $workspace = $this->repository->findOrFail($id);
        $ownerId = Auth::id();

        if ($workspace->owner_id !== $ownerId) {
            throw new Exception('Unauthorized to move this workspace.', 403);
        }

        $newDepth = 1;

        if ($parentId) {
            if ($parentId === $id) {
                throw new Exception('Cannot move a workspace to itself.', 422);
            }

            $parent = $this->repository->findOrFail($parentId);

            if ($parent->owner_id !== $ownerId) {
                throw new Exception('Parent workspace does not belong to you.', 403);
            }

            if ($this->repository->isDescendant($id, $parentId)) {
                throw new Exception('Circular dependency detected: Cannot move a workspace to its own descendant.', 422);
            }

            $newDepth = $parent->depth + 1;
        }

        $subtreeHeight = $this->repository->getSubtreeHeight($workspace);

        if (($newDepth + $subtreeHeight - 1) > 3) {
            throw new Exception('Moving this workspace would exceed the maximum depth of 3.', 422);
        }

        $workspace = $this->repository->update($workspace, ['parent_id' => $parentId]);
        $this->repository->updateSubtreeDepths($workspace, $newDepth);

        return $workspace;
    }
}
