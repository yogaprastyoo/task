<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return ApiResponse::success($request->user(), 'Authenticated user');
    });

    Route::get('/workspaces/root', [WorkspaceController::class, 'root']);
    Route::get('/workspaces/trash', [WorkspaceController::class, 'trash']);
    Route::get('/workspaces/archived', [WorkspaceController::class, 'archived']);
    Route::apiResource('/workspaces', WorkspaceController::class);

    Route::patch('/workspaces/{id}/move', [WorkspaceController::class, 'move']);
    Route::patch('/workspaces/{id}/archive', [WorkspaceController::class, 'archive']);
    Route::get('/workspaces/{id}/breadcrumbs', [WorkspaceController::class, 'breadcrumbs']);
    Route::post('/workspaces/{workspace}/restore', [WorkspaceController::class, 'restore'])->withTrashed();

    // Task Routes
    Route::prefix('workspaces/{workspaceId}')->group(function () {
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
    });

    Route::apiResource('tasks', TaskController::class)->only(['show', 'update', 'destroy']);
});
