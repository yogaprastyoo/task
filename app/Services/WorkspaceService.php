<?php

namespace App\Services;

use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceService
{
    public function __construct(
        protected WorkspaceRepository $repository
    ) {}

    /**
     * Get all workspaces for the authenticated user.
     */
    public function getWorkspaces(int $userId): Collection
    {
        return $this->repository->getByOwner($userId);
    }

    /**
     * Find a specific workspace with ownership validation.
     */
    public function findWorkspace(int $userId, int $id): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== $userId) {
            throw new Exception('Unauthorized to access this workspace.', 403);
        }

        return $workspace;
    }

    /**
     * Create a new workspace (Root or Child).
     */
    public function createWorkspace(int $userId, array $data): Workspace
    {
        $parentId = $data['parent_id'] ?? null;
        $depth = 1;

        if ($parentId) {
            $parent = $this->repository->findOrFail($parentId);

            if ($parent->owner_id !== $userId) {
                throw new Exception('Parent workspace does not belong to you.', 403);
            }

            $depth = $parent->depth + 1;
        }

        if ($depth > 3) {
            throw new Exception('Maximum workspace depth of 3 reached.', 422);
        }

        if ($this->repository->findByNameAndParent($userId, $parentId, $data['name'])) {
            $levelMessage = $parentId ? 'at this parent level' : 'at the root level';
            throw new Exception("A workspace with this name already exists {$levelMessage}.", 422);
        }

        return $this->repository->create([
            'name' => $data['name'],
            'owner_id' => $userId,
            'parent_id' => $parentId,
            'depth' => $depth,
        ]);
    }

    /**
     * Rename an existing workspace.
     */
    public function renameWorkspace(int $userId, int $id, string $name): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== $userId) {
            throw new Exception('Unauthorized to update this workspace.', 403);
        }

        if ($name !== $workspace->name) {
            if ($this->repository->findByNameAndParent($userId, $workspace->parent_id, $name)) {
                $levelMessage = $workspace->parent_id ? 'at this parent level' : 'at the root level';
                throw new Exception("A workspace with this name already exists {$levelMessage}.", 422);
            }
        }

        return $this->repository->update($workspace, ['name' => $name]);
    }

    /**
     * Delete a workspace and all its descendants.
     */
    public function deleteWorkspace(int $userId, int $id): void
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== $userId) {
            throw new Exception('Unauthorized to delete this workspace.', 403);
        }

        $this->performRecursiveDelete($workspace);
    }

    /**
     * Helper to recursively delete descendants.
     */
    protected function performRecursiveDelete(Workspace $workspace): void
    {
        // Load children to avoid N+1 queries
        $workspace->load('children');

        foreach ($workspace->children as $child) {
            $this->performRecursiveDelete($child);
        }

        $this->repository->delete($workspace);
    }

    /**
     * Move a workspace to a new parent.
     */
    public function moveWorkspace(int $userId, int $id, ?int $parentId): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        if ($workspace->owner_id !== $userId) {
            throw new Exception('Unauthorized to move this workspace.', 403);
        }

        $newDepth = 1;

        if ($parentId) {
            if ($parentId === $id) {
                throw new Exception('Cannot move a workspace to itself.', 422);
            }

            $parent = $this->repository->findOrFail($parentId);

            if ($parent->owner_id !== $userId) {
                throw new Exception('Parent workspace does not belong to you.', 403);
            }

            if ($this->repository->isDescendant($id, $parent)) {
                throw new Exception('Circular dependency detected: Cannot move a workspace to its own descendant.', 422);
            }

            $newDepth = $parent->depth + 1;
        }

        // Check if sibling name conflict exists at the destination parent level
        // Only check if we are actually changing the parent
        if ($parentId !== $workspace->parent_id) {
            if ($this->repository->findByNameAndParent($userId, $parentId, $workspace->name)) {
                $levelMessage = $parentId ? 'at this parent level' : 'at the root level';
                throw new Exception("A workspace with this name already exists {$levelMessage}.", 422);
            }
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
