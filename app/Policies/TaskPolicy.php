<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can update the status of the task.
     */
    public function updateStatus(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can move the task within the workspace.
     */
    public function move(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can create a sub-task under the task.
     */
    public function createSubTask(User $user, Task $task): bool|Response
    {
        return $user->id === $task->creator_id
            ? true
            : Response::deny('You do not own this task.');
    }
}
