<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use App\Services\WorkspaceService;
use Exception;
use Tests\TestCase;

class WorkspaceServiceTest extends TestCase
{
    protected WorkspaceRepository $repository;

    protected WorkspaceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(WorkspaceRepository::class);
        $this->service = new WorkspaceService($this->repository);
    }

    public function test_it_calculates_depth_one_for_root_workspaces(): void
    {

        $this->repository->expects($this->once())
            ->method('findByNameAndParent')
            ->with(1, null, 'Root')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return $data['depth'] === 1 && $data['parent_id'] === null;
            }))
            ->willReturn(new Workspace);

        $user = new User;
        $user->id = 1;
        $this->actingAs($user);

        $this->service->createWorkspace(1, ['name' => 'Root']);
    }

    public function test_it_calculates_depth_two_for_child_of_root(): void
    {

        $parent = new Workspace;
        $parent->id = 10;
        $parent->owner_id = 1;
        $parent->depth = 1;

        $this->repository->method('findOrFail')->with(10)->willReturn($parent);
        $this->repository->method('findByNameAndParent')->willReturn(null);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return $data['depth'] === 2 && $data['parent_id'] === 10;
            }))
            ->willReturn(new Workspace);

        $user = new User;
        $user->id = 1;
        $this->actingAs($user);

        $this->service->createWorkspace(1, ['name' => 'Level 2', 'parent_id' => 10]);
    }

    public function test_it_throws_exception_when_depth_exceeds_three(): void
    {

        $parent = new Workspace;
        $parent->id = 20;
        $parent->owner_id = 1;
        $parent->depth = 3;

        $this->repository->method('findOrFail')->with(20)->willReturn($parent);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Maximum workspace depth of 3 reached.');

        $user = new User;
        $user->id = 1;
        $this->actingAs($user);

        $this->service->createWorkspace(1, ['name' => 'Level 4', 'parent_id' => 20]);
    }

    public function test_it_throws_exception_when_parent_belongs_to_another_user(): void
    {

        $parent = new Workspace;
        $parent->id = 30;
        $parent->owner_id = 2; // Different user
        $parent->depth = 1;

        $this->repository->method('findOrFail')->with(30)->willReturn($parent);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Parent workspace does not belong to you.');

        $user = new User;
        $user->id = 1;
        $this->actingAs($user);

        $this->service->createWorkspace(1, ['name' => 'Stolen Parent', 'parent_id' => 30]);
    }
}
