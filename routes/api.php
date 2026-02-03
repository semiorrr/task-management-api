<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('users', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class)->except(['store']);
    Route::apiResource('tasks', TaskController::class);

    Route::get('users/{id}/tasks', [UserController::class, 'tasksByUser']);
    Route::get('tasks/{id}/user', [TaskController::class, 'userByTask']);
});
