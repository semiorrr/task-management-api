<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $includes = array_filter(explode(',', $request->query('include', '')));

        $query = Task::query();

        if (in_array('user', $includes)) {
            $query->with('user');
        }

        return $query->get();
    }

    public function show(Request $request, $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json(['message' => 'Tasks not found. '], 404);
        }

        $includes = array_filter(explode(',', $request->query('include', '')));

        if (in_array('user', $includes)) {
            $task->load('user');
        }

        return $task;
    }

    public function userByTask($id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json(['message' => 'Tasks not found. '], 404);
        }

        if (! $task->user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return $task->user;
    }

    public function store(Request $request)
    {
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

        $task = Task::create($validated);

        return response()->json($task, 201);
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json(['message' => 'Tasks not found. '], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'user_id' => 'sometimes|integer',
        ]);

        if (isset($validated['user_id']) && ! User::where('id', $validated['user_id'])->exists()) {
            return response()->json(['message' => 'User not found'], 400);
        }

        if (isset($validated['title']) && Task::where('title', $validated['title'])
            ->where('id', '!=', $task->id)
            ->exists()) {
            return response()->json(['message' => 'Title already exist'], 400);
        }

        $task->update($validated);

        return $task;
    }

    public function destroy($id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json(['message' => 'Tasks not found. '], 404);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
}
