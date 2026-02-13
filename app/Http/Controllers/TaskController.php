<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $includes = array_filter(explode(',', $request->query('include', '')));
        $query = Task::query();

        
        if ($user->role === 'team_leader') {
            $query->where('team_id', $user->team_id)->orWhere('user_id', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        if (in_array('user', $includes)) {
            $query->with('user');
        }

        return $query->get();
    }

    public function show(Request $request, Task $task)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin' && $authUser->role !== 'team_leader' && $authUser->id !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $includes = array_filter(explode(',', $request->query('include', '')));

        if (in_array('user', $includes)) {
            $task->load('user');
        }

        return response()->json($task);
    }

    public function userByTask($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Tasks not found. '], 404);
        }

        if (!$task->user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return $task->user;
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'user_id' => 'required|integer',
        ]);

        if (! User::where('id', $validated['user_id'])->exists()) {
            return response()->json(['message' => 'User not found'], 400);
        }

        if (Task::where('title', $validated['title'])->exists()) {
            return response()->json(['message' => 'Title already exist'], 400);
        }

        // Team leaders can only create tasks for their team members
        if ($authUser->role === 'team_leader') {
            $targetUser = User::find($validated['user_id']);
            if ($targetUser->team_id !== $authUser->team_id) {
                return response()->json(['message' => 'Cannot create task for user outside your team'], 403);
            }
            $validated['team_id'] = $authUser->team_id;
        } elseif ($authUser->role !== 'admin') {
            // Regular users can only create tasks for themselves
            $validated['user_id'] = $authUser->id;
            $validated['team_id'] = $authUser->team_id;
        }

        $task = Task::create($validated);

        return response()->json($task, 201);
    }

    public function update(Request $request, Task $task)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin' && $authUser->role !== 'team_leader' && $authUser->id !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'user_id' => 'nullable|integer',
        ]);

        $validated = array_filter($validated, fn($value) => $value !== null);

        if (isset($validated['user_id']) && ! User::where('id', $validated['user_id'])->exists()) {
            return response()->json(['message' => 'User not found'], 400);
        }

        if (isset($validated['title']) && Task::where('title', $validated['title'])
            ->where('id', '!=', $task->id)
            ->exists()) {
            return response()->json(['message' => 'Title already exist'], 400);
        }

        $task->update($validated);

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin' && $authUser->role !== 'team_leader' && $authUser->id !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
}
    