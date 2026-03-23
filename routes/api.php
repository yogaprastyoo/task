<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::get('/user', function (Request $request) {
    return ApiResponse::success($request->user(), 'Authenticated user');
})->middleware('auth:sanctum');
