<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    public function index(Request $request)
    {
        $user = auth()->user();
        $includes = array_filter(explode(',', $request->query('include', '')));

        $query = User::query();

        if ($user->role === 'team_leader') {
            $query->where('team_id', $user->team_id)->orWhere('id', $user->id);
        } elseif ($user->role === 'user') {
            
            $query->where('id', $user->id);
        }

        if (in_array('tasks', $includes)) {
            $query->with('tasks');
        }

        if (in_array('team', $includes)) {
            $query->with('team');
        }

        return response()->json($query->get());
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:admin,team_leader,user',
        ]);

        if (User::where('name', $validated['name'])->exists()) {
            return response()->json(['message' => 'Name already exist'], 400);
        }

        $validated['password'] = bcrypt($validated['password']);
        
        $authUser = auth('sanctum')->user();
        
        if ($authUser) {
            //admin and team_leader can create users
            if ($authUser->role === 'team_leader') {
                $validated['role'] = 'user';
                $validated['team_id'] = $authUser->team_id;
            } elseif ($authUser->role === 'admin') {
                //admin can set any role
                $validated['role'] = $validated['role'] ?? 'user';
            } else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            //Unauthenticated users create with default 'user' role
            $validated['role'] = 'user';
        }
        
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(Request $request, User $user)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin' && $authUser->role !== 'team_leader' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $includes = array_filter(explode(',', $request->query('include', '')));

        if (in_array('tasks', $includes)) {
            $user->load('tasks');
        }

        if (in_array('team', $includes)) {
            $user->load('team');
        }

        return response()->json($user);
    }

    public function tasksByUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return $user->tasks;
    }

  
    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();
        if ($authUser->role === 'team_leader' && $authUser->team_id !== $user->team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($authUser->role === 'user' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|in:admin,team_leader,user',
            'team_id' => 'nullable|integer|exists:teams,id',
        ]);

        // Remove null values to avoid overwriting with nulls
        $validated = array_filter($validated, fn($value) => $value !== null);

        // Only admin can change role or team_id
        if (isset($validated['role']) && $authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can change roles'], 403);
        }
        if (isset($validated['team_id']) && $authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can change team assignments'], 403);
        }

        if (isset($validated['name']) && User::where('name', $validated['name'])
            ->where('id', '!=', $user->id)
            ->exists()) {
            return response()->json(['message' => 'Name already exist'], 400);
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);
        $user->refresh();

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $authUser = auth()->user();
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
