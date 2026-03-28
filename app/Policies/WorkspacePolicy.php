<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to access this workspace.');
    }

    /**
     * Determine whether the user can view a parent workspace.
     */
    public function viewParent(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Parent workspace does not belong to you.');
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to update this workspace.');
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to delete this workspace.');
    }

    /**
     * Determine whether the user can restore the workspace.
     */
    public function restore(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to restore this workspace.');
    }

    /**
     * Determine whether the user can archive the workspace.
     */
    public function archive(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to archive this workspace.');
    }

    /**
     * Determine whether the user can move the workspace.
     */
    public function move(User $user, Workspace $workspace): bool|Response
    {
        return $user->id === $workspace->owner_id
            ? true
            : Response::deny('Unauthorized to move this workspace.');
    }
}
