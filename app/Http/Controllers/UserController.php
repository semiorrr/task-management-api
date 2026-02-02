<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    public function index(Request $request)
    {
        $includes = array_filter(explode(',', $request->query('include', '')));

        $query = User::query();

        if (in_array('tasks', $includes)) {
            $query->with('tasks');
        }

        return $query->get();
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (User::where('name', $validated['name'])->exists()) {
            return response()->json(['message' => 'Name already exist'], 400);
        }

        $validated['password'] = bcrypt($validated['password']);
        
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(Request $request, User $user)
    {
        $includes = array_filter(explode(',', $request->query('include', '')));

        if (in_array('tasks', $includes)) {
            $user->load('tasks');
        }

        return $user;
    }

    public function tasksByUser($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return $user->tasks;
    }

  
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($validated['name']) && User::where('name', $validated['name'])
            ->where('id', '!=', $user->id)
            ->exists()) {
            return response()->json(['message' => 'Name already exist'], 400);
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return $user;
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
