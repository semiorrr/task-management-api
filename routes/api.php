<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('users', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // Users endpoints with authorization
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    // Teams endpoints with authorization
    Route::get('teams', [TeamController::class, 'index']);
    Route::post('teams', [TeamController::class, 'store'])->middleware(App\Http\Middleware\AdminOnly::class);
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::patch('teams/{team}', [TeamController::class, 'update'])->middleware(App\Http\Middleware\TeamLeaderOwnTeam::class);
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->middleware(App\Http\Middleware\TeamLeaderOwnTeam::class);

    // Team members management
    Route::post('teams/{team}/members', [TeamController::class, 'addMember'])
        ->middleware([
            App\Http\Middleware\NormalUsersCannotManageMembers::class,
            App\Http\Middleware\ManageTeamMembers::class,
        ]);
    Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])
        ->middleware([
            App\Http\Middleware\NormalUsersCannotManageMembers::class,
            App\Http\Middleware\ManageTeamMembers::class,
        ]);

    // Tasks endpoints with authorization
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::patch('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    Route::get('users/{id}/tasks', [UserController::class, 'tasksByUser']);
    Route::get('tasks/{id}/user', [TaskController::class, 'userByTask']);
});
