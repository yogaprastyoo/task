<?php

namespace Database\Seeders;

use App\Models\Task;
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

        if ($workspaces->isEmpty()) {
            return;
        }

        foreach ($workspaces as $workspace) {
            $tasks = Task::factory()->count(fake()->numberBetween(3, 5))->create([
                'workspace_id' => $workspace->id,
                'creator_id' => $workspace->owner_id,
            ]);

            foreach ($tasks as $task) {
                if (fake()->boolean()) {
                    Task::factory()->count(fake()->numberBetween(1, 2))->withParent($task)->create();
                }
            }
        }
    }
}
