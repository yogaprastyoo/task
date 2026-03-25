<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspaces = Workspace::all();
        $users = User::all();

        if ($workspaces->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($workspaces as $workspace) {
            // Create 3-5 root tasks per workspace
            $tasks = Task::factory()->count(rand(3, 5))->create([
                'workspace_id' => $workspace->id,
                'creator_id' => $workspace->owner_id,
            ]);

            foreach ($tasks as $task) {
                // Create 1-2 sub-tasks for some tasks
                if (rand(0, 1)) {
                    Task::factory()->count(rand(1, 2))->withParent($task)->create();
                }
            }
        }
    }
}
