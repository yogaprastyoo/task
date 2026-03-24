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
     * Get all workspaces owned by a user.
     */
    public function getByOwner(int $ownerId): Collection
    {
        return Workspace::where('owner_id', $ownerId)->get();
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
    public function isDescendant(int $parentId, int $childId): bool
    {
        $child = $this->findOrFail($childId);

        if ($child->parent_id === $parentId) {
            return true;
        }

        if ($child->parent_id === null) {
            return false;
        }

        return $this->isDescendant($parentId, $child->parent_id);
    }

    /**
     * Get the maximum height of the subtree starting from this workspace.
     * Root of subtree is height 1.
     */
    public function getSubtreeHeight(Workspace $workspace): int
    {
        $maxChildHeight = 0;

        foreach ($workspace->children as $child) {
            $childHeight = $this->getSubtreeHeight($child);
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

        foreach ($workspace->children as $child) {
            $this->updateSubtreeDepths($child, $newDepth + 1);
        }
    }
}
