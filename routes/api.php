<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Guest Only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest.api')
    ->controller(AuthController::class)
    ->prefix('auth')
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->group(function () {
            Route::post('/logout', 'logout');
        });

    Route::get('/user', function (Request $request) {
        return ApiResponse::success($request->user(), 'Authenticated user');
    });

    /*
    |------------------------------------------------------------------
    | Workspace Routes
    |------------------------------------------------------------------
    */
    Route::controller(WorkspaceController::class)
        ->prefix('workspaces')
        ->group(function () {
            // Collection-level custom routes
            Route::get('/root', 'root');
            Route::get('/trash', 'trash');
            Route::get('/archived', 'archived');

            // Member-level custom routes
            Route::patch('/{workspace}/move', 'move');
            Route::patch('/{workspace}/archive', 'archive');
            Route::get('/{workspace}/breadcrumbs', 'breadcrumbs');
            Route::post('/{workspace}/restore', 'restore');
        });

    Route::apiResource('workspaces', WorkspaceController::class);

    /*
    |------------------------------------------------------------------
    | Task Routes
    |------------------------------------------------------------------
    */
    Route::controller(TaskController::class)
        ->prefix('tasks')
        ->group(function () {
            // Member-level custom routes
            Route::patch('/{task}/move', 'move');
            Route::patch('/{task}/status', 'status');
            Route::post('/{task}/subtasks', 'subtask');
        });

    Route::apiResource('workspaces.tasks', TaskController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});
