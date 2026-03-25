<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.api')->controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::middleware('auth:sanctum')->group(function () {
    // User Routes
    Route::get('/user', function (Request $request) {
        return ApiResponse::success($request->user(), 'Authenticated user');
    });

    // Workspace Custom Routes
    Route::controller(WorkspaceController::class)->prefix('workspaces')->group(function () {
        Route::get('/root', 'root');
        Route::get('/trash', 'trash');
        Route::get('/archived', 'archived');
        
        Route::patch('/{workspace}/move', 'move');
        Route::patch('/{workspace}/archive', 'archive');
        Route::get('/{workspace}/breadcrumbs', 'breadcrumbs');
        Route::post('/{workspace}/restore', 'restore')->withTrashed();
    });
    
    // Workspace Standard Resource
    Route::apiResource('workspaces', WorkspaceController::class);

    // Task Custom Routes
    Route::controller(TaskController::class)->prefix('tasks')->group(function () {
        Route::patch('/{task}/status', 'status');
    });

    // Task Nested Resource
    Route::apiResource('workspaces.tasks', TaskController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});
