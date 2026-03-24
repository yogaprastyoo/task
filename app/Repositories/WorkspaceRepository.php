<?php

namespace App\Repositories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceRepository
{
    /**
     * Create a new workspace.
     */
    public function create(array $data): Workspace
    {
        return Workspace::create($data);
    }

    /**
     * Find a workspace by ID or throw an exception.
     */
    public function findOrFail(int $id): Workspace
    {
        return Workspace::findOrFail($id);
    }

    /**
     * Find a workspace by ID, including soft-deleted ones.
     */
    public function findWithTrashed(int $id): Workspace
    {
        return Workspace::withTrashed()->findOrFail($id);
    }

    /**
     * Update a workspace.
     */
    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update($data);

        return $workspace->refresh();
    }

    /**
     * Delete a workspace.
     */
    public function delete(Workspace $workspace): bool
    {
        return $workspace->delete();
    }

    /**
     * Restore a soft-deleted workspace.
     */
    public function restore(Workspace $workspace): bool
    {
        return $workspace->restore();
    }

    /**
     * Get all workspaces owned by a user.
     */
    public function getByOwner(int $ownerId, bool $includeArchived = false): Collection
    {
        $query = Workspace::withCount('children')
            ->where('owner_id', $ownerId);

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        return $query->get();
    }

    /**
     * Get root workspaces owned by a user.
     */
    public function getRootByOwner(int $ownerId, bool $includeArchived = false): Collection
    {
        $query = Workspace::withCount('children')
            ->where('owner_id', $ownerId)
            ->whereNull('parent_id');

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        return $query->get();
    }

    /**
     * Find a workspace by owner, parent, and name (for uniqueness check).
     */
    public function findByNameAndParent(int $ownerId, ?int $parentId, string $name): ?Workspace
    {
        return Workspace::where('owner_id', $ownerId)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->first();
    }

    /**
     * Check if a workspace is a descendant of another.
     */
    public function isDescendant(int $parentId, Workspace $child): bool
    {
        if ($child->parent_id === $parentId) {
            return true;
        }

        if ($child->parent_id === null) {
            return false;
        }

        $parent = $this->findOrFail($child->parent_id);

        return $this->isDescendant($parentId, $parent);
    }

    /**
     * Get the maximum height of the subtree starting from this workspace.
     * Root of subtree is height 1.
     */
    public function getSubtreeHeight(Workspace $workspace): int
    {
        // Eager load descendants to avoid N+1 queries during recursion (max depth 3)
        $workspace->load('children.children');

        return $this->calculateSubtreeHeight($workspace);
    }

    /**
     * Helper to recursively calculate height without redundant loads.
     */
    protected function calculateSubtreeHeight(Workspace $workspace): int
    {
        $maxChildHeight = 0;

        foreach ($workspace->children as $child) {
            $childHeight = $this->calculateSubtreeHeight($child);
            if ($childHeight > $maxChildHeight) {
                $maxChildHeight = $childHeight;
            }
        }

        return 1 + $maxChildHeight;
    }

    /**
     * Update the depth of a workspace and all its descendants recursively.
     */
    public function updateSubtreeDepths(Workspace $workspace, int $newDepth): void
    {
        $workspace->update(['depth' => $newDepth]);

        // Ensure children (including trashed) are loaded for the subtree update
        if (! $workspace->relationLoaded('children')) {
            $workspace->load(['children' => function ($query) {
                $query->withTrashed();
            }]);
        }

        foreach ($workspace->children as $child) {
            $this->updateSubtreeDepths($child, $newDepth + 1);
        }
    }

    /**
     * Get all soft-deleted workspaces for a specific owner.
     */
    public function getTrashedByOwner(int $ownerId): Collection
    {
        return Workspace::onlyTrashed()
            ->where('owner_id', $ownerId)
            ->get();
    }

    /**
     * Get all archived workspaces for a specific owner (top-level only).
     * Returns only archived workspaces whose parent is not archived.
     */
    public function getArchivedByOwner(int $ownerId): Collection
    {
        return Workspace::where('owner_id', $ownerId)
            ->where('is_archived', true)
            ->whereDoesntHave('parent', function ($query) {
                $query->withTrashed()->where('is_archived', true);
            })
            ->get();
    }

    /**
     * Bulk update the archive status for a list of workspace IDs.
     *
     * @param  array<int>  $ids
     */
    public function bulkUpdateArchiveStatus(array $ids, bool $status): void
    {
        Workspace::withTrashed()->whereIn('id', $ids)->update(['is_archived' => $status]);
    }

    /**
     * Search workspaces by name for a specific owner across all hierarchy levels.
     */
    public function searchByOwner(int $ownerId, string $keyword): Collection
    {
        return Workspace::with('parent.parent')
            ->where('owner_id', $ownerId)
            ->where('name', 'LIKE', "%{$keyword}%")
            ->get();
    }

    /**
     * Get the ancestors of a workspace from root down to the workspace itself.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getAncestors(Workspace $workspace): array
    {
        // Load parents recursively up to max depth (3) in one go
        $workspace->load('parent.parent');

        $ancestors = [];
        $current = $workspace;

        while ($current !== null) {
            array_unshift($ancestors, [
                'id' => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $ancestors;
    }
}
