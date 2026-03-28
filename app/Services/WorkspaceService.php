<?php

namespace App\Services;

use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkspaceService
{
    public function __construct(
        protected WorkspaceRepository $repository
    ) {}

    /**
     * Get all workspaces for the authenticated user.
     */
    public function getWorkspaces(int $userId, bool $includeArchived = false): Collection
    {
        return $this->repository->getByOwner($userId, $includeArchived);
    }

    /**
     * Get root workspaces for the authenticated user.
     */
    public function getRootWorkspaces(int $userId, bool $includeArchived = false): Collection
    {
        return $this->repository->getRootByOwner($userId, $includeArchived);
    }

    /**
     * Get isolated/top-level archived workspaces for the authenticated user.
     */
    public function getArchivedWorkspaces(int $userId): Collection
    {
        return $this->repository->getArchivedByOwner($userId);
    }

    /**
     * Find a specific workspace with ownership validation.
     */
    public function findWorkspace(int $userId, int $id): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        Gate::authorize('view', $workspace);

        // Eager load child workspaces and their count
        $workspace->load('children');
        $workspace->loadCount('children');

        return $workspace;
    }

    /**
     * Get the breadcrumbs (ancestor path) for a workspace.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getBreadcrumbs(int $userId, int $id): array
    {
        $workspace = $this->repository->findOrFail($id);

        Gate::authorize('view', $workspace);

        return $this->repository->getAncestors($workspace);
    }

    /**
     * Create a new workspace (Root or Child).
     */
    public function createWorkspace(int $userId, array $data): Workspace
    {
        return DB::transaction(function () use ($userId, $data) {
            $parentId = $data['parent_id'] ?? null;
            $depth = 1;

            if ($parentId) {
                $parent = $this->repository->findOrFail($parentId);

                Gate::authorize('viewParent', $parent);

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

            $settings = Workspace::DEFAULT_SETTINGS;
            if (isset($data['icon'])) {
                $settings['icon'] = $data['icon'];
            }
            if (isset($data['color'])) {
                $settings['color'] = $data['color'];
            }

            return $this->repository->create([
                'name' => $data['name'],
                'owner_id' => $userId,
                'parent_id' => $parentId,
                'depth' => $depth,
                'settings' => $settings,
                'is_archived' => $data['is_archived'] ?? false,
            ]);
        });
    }

    /**
     * Update an existing workspace.
     */
    public function updateWorkspace(int $userId, int $id, array $data): Workspace
    {
        $workspace = $this->repository->findOrFail($id);

        Gate::authorize('update', $workspace);

        $updateData = [];

        if (isset($data['name']) && $data['name'] !== $workspace->name) {
            if ($this->repository->findByNameAndParent($userId, $workspace->parent_id, $data['name'])) {
                $levelMessage = $workspace->parent_id ? 'at this parent level' : 'at the root level';
                throw ValidationException::withMessages([
                    'name' => ["A workspace with this name already exists {$levelMessage}."],
                ]);
            }
            $updateData['name'] = $data['name'];
        }

        // Handle settings merging

        if (isset($data['icon']) || isset($data['color'])) {
            $settings = $workspace->settings ?? [];
            if (isset($data['icon'])) {
                $settings['icon'] = $data['icon'];
            }
            if (isset($data['color'])) {
                $settings['color'] = $data['color'];
            }
            $updateData['settings'] = $settings;
        }

        return $this->repository->update($workspace, $updateData);
    }

    /**
     * Toggle archive status of a workspace.
     */
    public function archiveWorkspace(int $userId, int $id): Workspace
    {
        return DB::transaction(function () use ($id) {
            $workspace = $this->repository->findOrFail($id);

            Gate::authorize('archive', $workspace);

            $newStatus = ! $workspace->is_archived;
            $this->performRecursiveArchive($workspace, $newStatus);

            return $this->repository->update($workspace, [
                'is_archived' => $newStatus,
            ]);
        });
    }

    /**
     * Helper to recursively collect all descendant IDs and bulk-update archive status.
     */
    protected function performRecursiveArchive(Workspace $workspace, bool $status): void
    {
        $ids = $this->collectDescendantIds($workspace);

        if (! empty($ids)) {
            $this->repository->bulkUpdateArchiveStatus($ids, $status);
        }
    }

    /**
     * Recursively collect IDs of all descendants.
     *
     * @return array<int>
     */
    protected function collectDescendantIds(Workspace $workspace): array
    {
        $workspace->load(['children' => function ($query) {
            $query->withTrashed();
        }]);

        $ids = [];
        foreach ($workspace->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->collectDescendantIds($child));
        }

        return $ids;
    }

    /**
     * Delete a workspace and all its descendants.
     */
    public function deleteWorkspace(int $userId, int $id): void
    {
        DB::transaction(function () use ($id) {
            $workspace = $this->repository->findOrFail($id);

            Gate::authorize('delete', $workspace);

            $this->performRecursiveDelete($workspace);
        });
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
        return DB::transaction(function () use ($userId, $id, $parentId) {
            $workspace = $this->repository->findOrFail($id);

            Gate::authorize('move', $workspace);

            $newDepth = 1;

            if ($parentId) {
                if ($parentId === $id) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['Cannot move a workspace to itself.'],
                    ]);
                }

                $parent = $this->repository->findOrFail($parentId);

                Gate::authorize('viewParent', $parent);

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
        });
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
        return DB::transaction(function () use ($userId, $id) {
            $workspace = $this->repository->findWithTrashed($id);

            if (! $workspace->trashed()) {
                throw new NotFoundHttpException('Workspace not found in trash.');
            }

            Gate::authorize('restore', $workspace);

            if ($this->repository->findByNameAndParent($userId, $workspace->parent_id, $workspace->name)) {
                $levelMessage = $workspace->parent_id ? 'at this parent level' : 'at the root level';
                throw ValidationException::withMessages([
                    'name' => ["Cannot restore workspace because an active workspace with this name already exists {$levelMessage}."],
                ]);
            }

            $this->performRecursiveRestore($workspace);

            return $workspace->refresh();
        });
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

    /**
     * Search workspaces globally for a user and include their hierarchical path.
     */
    public function searchWorkspaces(int $userId, string $keyword): Collection
    {
        $workspaces = $this->repository->searchByOwner($userId, $keyword);

        return $workspaces->map(function ($workspace) {
            $workspace->path = $this->constructPath($workspace);

            return $workspace;
        });
    }

    /**
     * Helper to construct the full hierarchical path string for a workspace.
     */
    protected function constructPath(Workspace $workspace): string
    {
        $parts = [$workspace->name];
        $current = $workspace;

        // Since max depth is 3, parent.parent covers the full potential hierarchy
        while ($current->parent) {
            array_unshift($parts, $current->parent->name);
            $current = $current->parent;
        }

        return implode(' > ', $parts);
    }
}
