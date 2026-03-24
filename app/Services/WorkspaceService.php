<?php

namespace App\Services;

use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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
     * Get root workspaces for the authenticated user.
     */
    public function getRootWorkspaces(int $userId): Collection
    {
        return $this->repository->getRootByOwner($userId);
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

        // Eager load child workspaces to show contents
        $workspace->load('children');

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
            throw ValidationException::withMessages([
                'parent_id' => ['Maximum workspace depth of 3 reached.'],
            ]);
        }

        if ($this->repository->findByNameAndParent($userId, $parentId, $data['name'])) {
            $levelMessage = $parentId ? 'at this parent level' : 'at the root level';
            throw ValidationException::withMessages([
                'name' => ["A workspace with this name already exists {$levelMessage}."],
            ]);
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
                throw ValidationException::withMessages([
                    'name' => ["A workspace with this name already exists {$levelMessage}."],
                ]);
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
            abort(403, 'Unauthorized to delete this workspace.');
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
                throw ValidationException::withMessages([
                    'parent_id' => ['Cannot move a workspace to itself.'],
                ]);
            }

            $parent = $this->repository->findOrFail($parentId);

            if ($parent->owner_id !== $userId) {
                throw new Exception('Parent workspace does not belong to you.', 403);
            }

            if ($this->repository->isDescendant($id, $parent)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Circular dependency detected: Cannot move a workspace to its own descendant.'],
                ]);
            }

            $newDepth = $parent->depth + 1;
        }

        // Check if sibling name conflict exists at the destination parent level
        // Only check if we are actually changing the parent
        if ($parentId !== $workspace->parent_id) {
            if ($this->repository->findByNameAndParent($userId, $parentId, $workspace->name)) {
                $levelMessage = $parentId ? 'at this parent level' : 'at the root level';
                throw ValidationException::withMessages([
                    'name' => ["A workspace with this name already exists {$levelMessage}."],
                ]);
            }
        }

        $subtreeHeight = $this->repository->getSubtreeHeight($workspace);

        if (($newDepth + $subtreeHeight - 1) > 3) {
            throw ValidationException::withMessages([
                'parent_id' => ['Moving this workspace would exceed the maximum depth of 3.'],
            ]);
        }

        $workspace = $this->repository->update($workspace, ['parent_id' => $parentId]);
        $this->repository->updateSubtreeDepths($workspace, $newDepth);

        return $workspace;
    }

    /**
     * Get all trashed workspaces for a user.
     */
    public function getTrashedWorkspaces(int $userId): Collection
    {
        return $this->repository->getTrashedByOwner($userId);
    }

    /**
     * Restore a soft-deleted workspace and all its descendants.
     */
    public function restoreWorkspace(int $userId, int $id): Workspace
    {
        $workspace = $this->repository->findWithTrashed($id);

        if ($workspace->owner_id !== $userId) {
            abort(403, 'Unauthorized to restore this workspace.');
        }

        $this->performRecursiveRestore($workspace);

        return $workspace->refresh();
    }

    /**
     * Helper to recursively restore descendants.
     */
    protected function performRecursiveRestore(Workspace $workspace): void
    {
        // Load trashed children to avoid N+1 queries
        $workspace->load(['children' => function ($query) {
            $query->withTrashed();
        }]);

        foreach ($workspace->children as $child) {
            $this->performRecursiveRestore($child);
        }

        $this->repository->restore($workspace);
    }
}
