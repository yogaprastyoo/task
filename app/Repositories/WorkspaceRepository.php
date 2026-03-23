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
}
