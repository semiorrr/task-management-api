<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $includes = array_filter(explode(',', request()->query('include', '')));

        if ($authUser->role === 'admin') {
            $query = Team::query();
        } else {
            $query = Team::where('id', $authUser->team_id);
        }

        if (in_array('users', $includes)) {
            $query->with('members');
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'required|integer|exists:users,id',
        ]);

        $team = Team::create($validated);

        return response()->json($team, 201);
    }

    public function show(Team $team)
    {
        $includes = array_filter(explode(',', request()->query('include', '')));

        if (in_array('users', $includes)) {
            $team->load('members');
        }

        return response()->json($team);
    }

    public function addMember(Request $request, Team $team)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = \App\Models\User::find($validated['user_id']);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->team_id = $team->id;
        $user->save();

        return response()->json(['message' => 'Member added', 'user' => $user], 200);
    }

    public function removeMember(Team $team, $userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->team_id !== $team->id) {
            return response()->json(['message' => 'User is not a member of this team'], 400);
        }

        $user->team_id = null;
        $user->save();

        return response()->json(['message' => 'Member removed'], 200);
    }

    public function update(Request $request, Team $team)
    {
        $authUser = auth()->user();
        
        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can update teams'], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|integer|exists:users,id',
        ]);

        $validated = array_filter($validated, fn($value) => $value !== null);
        
        $team->update($validated);

        return response()->json($team);
    }

    public function destroy(Team $team)
    {
        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }
}
